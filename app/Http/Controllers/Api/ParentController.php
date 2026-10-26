<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentException;
use App\Exceptions\SlotUnavailableException;
use App\Models\AttendanceRecord;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\Payments\GatewayOrder;
use App\Services\Payments\MockGateway;
use App\Services\PricingService;
use App\Support\Platform;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParentController extends ApiController
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly PricingService $pricing,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $parent = $this->parentOrFail();
        $now = now();

        $upcoming = Booking::with(['teacher.user', 'student', 'subject'])
            ->where('parent_profile_id', $parent->id)
            ->where('start_at', '>=', $now)
            ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED])
            ->orderBy('start_at')
            ->limit(10)
            ->get();

        $recent = Booking::with(['teacher.user', 'student', 'subject'])
            ->where('parent_profile_id', $parent->id)
            ->orderByDesc('start_at')
            ->limit(5)
            ->get();

        return $this->ok([
            'parent' => [
                'id' => $parent->id,
                'name' => $request->user()->name,
                'city' => $parent->city,
            ],
            'stats' => [
                'children' => $parent->students()->count(),
                'upcoming_bookings' => Booking::where('parent_profile_id', $parent->id)
                    ->where('start_at', '>=', $now)
                    ->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED])
                    ->count(),
                'unpaid_bookings' => Booking::where('parent_profile_id', $parent->id)
                    ->where('payment_status', Booking::PAYMENT_UNPAID)
                    ->where('status', Booking::STATUS_PENDING_PAYMENT)
                    ->count(),
                'total_spent' => (float) Payment::where('parent_profile_id', $parent->id)
                    ->where('status', Payment::STATUS_PAID)->sum('amount'),
                'unread_notifications' => \App\Models\AppNotification::where('user_id', $request->user()->id)
                    ->whereNull('read_at')->count(),
            ],
            'upcoming_bookings' => $upcoming->map(fn (Booking $b) => $this->bookingPayload($b)),
            'recent_bookings' => $recent->map(fn (Booking $b) => $this->bookingPayload($b)),
            'notifications' => \App\Models\AppNotification::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')->limit(10)->get(),
        ]);
    }

    // --------------------------------------------------------------- students

    public function students(): JsonResponse
    {
        $parent = $this->parentOrFail();

        $students = $parent->students()->with(['classStandard', 'board'])->latest()->get();

        return $this->ok(['students' => $students]);
    }

    public function storeStudent(Request $request): JsonResponse
    {
        $parent = $this->parentOrFail();
        $student = $parent->students()->create($this->studentAttributes($request->validate($this->studentRules())) + ['is_active' => true]);

        return $this->ok(['student' => $student->load(['classStandard', 'board'])], 'Child profile created.', 201);
    }

    public function showStudent(StudentProfile $student): JsonResponse
    {
        $this->authorizeStudent($student);
        $student->load(['classStandard', 'board']);

        $bookings = $student->bookings()->with(['teacher.user', 'subject'])->latest()->limit(20)->get();

        return $this->ok(['student' => $student, 'bookings' => $bookings->map(fn (Booking $b) => $this->bookingPayload($b))]);
    }

    public function updateStudent(Request $request, StudentProfile $student): JsonResponse
    {
        $this->authorizeStudent($student);
        $student->update($this->studentAttributes($request->validate($this->studentRules())));

        return $this->ok(['student' => $student->fresh()->load(['classStandard', 'board'])], 'Child profile updated.');
    }

    public function destroyStudent(StudentProfile $student): JsonResponse
    {
        $this->authorizeStudent($student);
        $student->delete();

        return $this->ok(null, 'Child profile removed.');
    }

    // --------------------------------------------------------------- bookings

    public function bookings(Request $request): JsonResponse
    {
        $parent = $this->parentOrFail();

        $query = Booking::where('parent_profile_id', $parent->id)
            ->with(['teacher.user', 'subject', 'classStandard', 'board', 'student', 'review']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        } elseif ($request->boolean('upcoming')) {
            $query->where('start_at', '>=', now())->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED]);
        } elseif ($request->boolean('past')) {
            $query->where('start_at', '<', now());
        }

        $paginator = $query->orderByDesc('start_at')->paginate($this->perPage());

        return $this->paginated($paginator, fn (Booking $b) => $this->bookingPayload($b));
    }

    public function booking(Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        $booking->load([
            'teacher.user', 'teacher.teacherSubjects.subject', 'student', 'subject',
            'classStandard', 'board', 'location', 'payments', 'statusHistories',
            'onlineSession', 'attendance', 'review',
        ]);

        return $this->ok(['booking' => $this->bookingPayload($booking, true)]);
    }

    public function storeBooking(Request $request): JsonResponse
    {
        $parent = $this->parentOrFail();

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'student_id' => ['required', 'integer', 'exists:student_profiles,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'teacher_location_id' => ['nullable', 'integer', 'exists:teacher_locations,id'],
            'mode' => ['required', Rule::in(['online', 'offline'])],
            'start_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'discount_code' => ['nullable', 'string', 'max:40'],
        ]);

        $student = $parent->students()->find($validated['student_id']);
        abort_unless($student !== null, 403, 'You can only book for your own children.');

        $teacher = TeacherProfile::where('verification_status', 'approved')->find($validated['teacher_id']);
        abort_unless($teacher !== null, 404, 'Teacher not found.');

        $subject = isset($validated['subject_id']) ? \App\Models\Subject::find($validated['subject_id']) : null;
        $classStandard = isset($validated['class_standard_id']) ? \App\Models\ClassStandard::find($validated['class_standard_id']) : null;
        $board = isset($validated['board_id']) ? \App\Models\Board::find($validated['board_id']) : null;

        $quote = $this->pricing->quote(
            $teacher,
            (int) $validated['duration_minutes'],
            $validated['mode'],
            $subject,
            $classStandard,
            $board,
            $validated['discount_code'] ?? null
        );

        if ((float) $quote['hourly_rate'] <= 0) {
            return $this->error('This teacher has not published a fee for the selected subject and mode.', 422);
        }

        $start = Carbon::parse($validated['start_at']);
        $end = $start->copy()->addMinutes((int) $validated['duration_minutes']);

        try {
            $booking = $this->bookings->create(
                $parent,
                $student,
                $teacher,
                [
                    'mode' => $validated['mode'],
                    'start_at' => $start,
                    'end_at' => $end,
                    'duration_minutes' => (int) $validated['duration_minutes'],
                    'subject_id' => $validated['subject_id'] ?? null,
                    'class_standard_id' => $validated['class_standard_id'] ?? null,
                    'board_id' => $validated['board_id'] ?? null,
                    'teacher_location_id' => $validated['teacher_location_id'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ],
                $quote
            );
        } catch (SlotUnavailableException $exception) {
            return $this->error($exception->getMessage(), 409);
        }

        return $this->ok([
            'booking' => $this->bookingPayload($booking->load(['teacher.user', 'student', 'subject'])),
            'quote' => $quote,
        ], 'Booking created. Complete the payment to confirm it.', 201);
    }

    public function pay(Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        if ($booking->payment_status === Booking::PAYMENT_PAID) {
            return $this->error('This booking is already paid.', 422);
        }

        $payment = $this->payments->createOrder($booking, $this->parentOrFail(), request()->user());
        $gateway = $this->payments->gateway();

        $checkout = $gateway->checkoutConfig(new GatewayOrder(
            $payment->gateway_order_id,
            (float) $payment->amount,
            $payment->currency,
            (string) config('edubook.razorpay.key_id') ?: 'mock_public_key',
            $payment->order_id,
        ));

        return $this->ok([
            'payment' => $payment,
            'checkout' => $checkout,
            'is_mock_gateway' => $gateway->name() === 'mock',
        ], 'Payment order created.');
    }

    public function confirmPayment(Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        $payment = $booking->payments()->where('status', Payment::STATUS_CREATED)->latest()->first();

        if (! $payment) {
            return $this->error('There is no pending payment for this booking.', 422);
        }

        $gateway = $this->payments->gateway();

        if (! $gateway instanceof MockGateway) {
            return $this->error('This gateway must be confirmed from the checkout screen.', 422);
        }

        $order = new GatewayOrder(
            $payment->gateway_order_id,
            (float) $payment->amount,
            $payment->currency,
            'mock_public_key',
            $payment->order_id,
        );

        try {
            $this->payments->capture($payment, $gateway->signedCallback($order));
        } catch (PaymentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->ok([
            'payment' => $payment->fresh(),
            'booking' => $this->bookingPayload($booking->fresh()->load(['teacher.user', 'student', 'subject'])),
        ], 'Payment successful. Your booking is confirmed.');
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $payment = $booking->payments()
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_REFUNDED])
            ->latest()->first();

        $feePercent = (float) Platform::get('cancellation_fee_percent', 0);
        $windowHours = (int) Platform::get('cancellation_window_hours', 24);

        $refundAmount = 0.0;
        $cancellationFee = 0.0;

        if ($payment) {
            $gross = (float) $payment->amount;
            $hoursUntil = max(0, (int) now()->diffInHours($booking->start_at, false));

            if ($feePercent > 0 && $hoursUntil < $windowHours) {
                $cancellationFee = round($gross * ($feePercent / 100), 2);
            }

            $refundAmount = round($gross - $cancellationFee, 2);
        }

        $this->bookings->cancel($booking, $validated['reason'], 'parent');

        if ($payment && $refundAmount > 0) {
            $this->payments->refund(
                $payment,
                $refundAmount,
                'Parent cancellation',
                $request->user()->id,
                null,
                'Cancellation fee: '.$cancellationFee
            );
        }

        return $this->ok([
            'booking' => $this->bookingPayload($booking->fresh()),
            'refunded' => $refundAmount,
            'cancellation_fee' => $cancellationFee,
        ], 'Booking cancelled.');
    }

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        $validated = $request->validate([
            'start_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
        ]);

        $start = Carbon::parse($validated['start_at']);
        $end = $start->copy()->addMinutes((int) $validated['duration_minutes']);

        try {
            $this->bookings->reschedule($booking, $start, $end);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'Your class has been rescheduled.');
    }

    public function complete(Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        try {
            $this->bookings->complete($booking, 'parent');
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'Session marked as completed. Please rate your teacher.');
    }

    public function attendance(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['present', 'absent', 'late'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        AttendanceRecord::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'marked_by' => $request->user()->id,
                'marked_at' => now(),
            ]
        );

        $booking->update(['attendance_status' => $validated['status']]);

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'Attendance recorded.');
    }

    // --------------------------------------------------------------- payments

    public function payments(Request $request): JsonResponse
    {
        $parent = $this->parentOrFail();

        $query = Payment::where('parent_profile_id', $parent->id)
            ->with(['booking.teacher.user', 'booking.subject', 'refunds']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $paginator = $query->orderByDesc('created_at')->paginate($this->perPage());

        return $this->paginated($paginator);
    }

    public function payment(Payment $payment): JsonResponse
    {
        $this->authorizePayment($payment);
        $payment->load(['booking.teacher.user', 'booking.subject', 'booking.student', 'refunds']);

        return $this->ok(['payment' => $payment]);
    }

    public function receipt(Payment $payment): JsonResponse
    {
        $this->authorizePayment($payment);

        return $this->ok(['receipt' => $this->payments->receipt($payment)]);
    }

    // ---------------------------------------------------------------- reviews

    public function reviews(): JsonResponse
    {
        $parent = $this->parentOrFail();

        $reviews = Review::where('parent_profile_id', $parent->id)
            ->with(['teacher.user', 'booking.subject', 'student'])
            ->latest()->paginate($this->perPage());

        return $this->paginated($reviews);
    }

    public function storeReview(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($booking);

        if ($booking->status !== Booking::STATUS_COMPLETED) {
            return $this->error('You can only review a teacher after the session is completed.', 422);
        }

        if ($booking->review()->exists()) {
            return $this->error('You have already reviewed this session.', 422);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $review = Review::create([
            'booking_id' => $booking->id,
            'parent_profile_id' => $this->parentOrFail()->id,
            'teacher_profile_id' => $booking->teacher_profile_id,
            'student_profile_id' => $booking->student_profile_id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'is_anonymous' => (bool) ($validated['is_anonymous'] ?? false),
            'status' => 'published',
        ]);

        $this->recalculateTeacherRating($booking->teacher_profile_id);

        return $this->ok(['review' => $review], 'Thank you for your review.', 201);
    }

    public function updateReview(Request $request, Review $review): JsonResponse
    {
        $this->authorizeReview($review);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($validated);
        $this->recalculateTeacherRating($review->teacher_profile_id);

        return $this->ok(['review' => $review->fresh()], 'Your review has been updated.');
    }

    public function replyToReview(Request $request, Review $review): JsonResponse
    {
        $this->authorizeReview($review);

        $validated = $request->validate(['reply' => ['required', 'string', 'max:1000']]);

        $review->update(['teacher_reply' => $validated['reply'], 'replied_at' => now()]);

        return $this->ok(['review' => $review->fresh()], 'Your reply has been published.');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return array<string, mixed>
     */
    protected function bookingPayload(Booking $booking, bool $detail = false): array
    {
        $payload = [
            'id' => $booking->id,
            'reference_no' => $booking->reference_no,
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'mode' => $booking->mode,
            'start_at' => optional($booking->start_at)->toIso8601String(),
            'end_at' => optional($booking->end_at)->toIso8601String(),
            'duration_minutes' => (int) $booking->duration_minutes,
            'hourly_rate' => (float) $booking->hourly_rate,
            'total_amount' => (float) $booking->total_amount,
            'currency' => $booking->currency,
            'attendance_status' => $booking->attendance_status,
            'notes' => $booking->notes,
            'teacher' => $booking->teacher ? [
                'id' => $booking->teacher->id,
                'name' => $booking->teacher->user?->name,
                'photo_path' => $booking->teacher->photo_path,
                'rating_average' => (float) $booking->teacher->rating_average,
                'city' => $booking->teacher->city,
                'mobile' => $booking->relationLoaded('teacher') ? $booking->teacher->user?->mobile : null,
            ] : null,
            'student' => $booking->student ? ['id' => $booking->student->id, 'name' => $booking->student->display_name] : null,
            'subject' => $booking->subject ? ['id' => $booking->subject->id, 'name' => $booking->subject->name] : null,
            'class_standard' => $booking->classStandard ? ['id' => $booking->classStandard->id, 'name' => $booking->classStandard->name] : null,
            'board' => $booking->board ? ['id' => $booking->board->id, 'name' => $booking->board->name] : null,
            'location' => $booking->location ? [
                'id' => $booking->location->id,
                'label' => $booking->location->label,
                'city' => $booking->location->city,
            ] : null,
            'review' => $booking->relationLoaded('review') && $booking->review ? [
                'id' => $booking->review->id,
                'rating' => (int) $booking->review->rating,
                'comment' => $booking->review->comment,
            ] : null,
            'can' => [
                'pay' => $booking->payment_status === Booking::PAYMENT_UNPAID && $booking->status === Booking::STATUS_PENDING_PAYMENT,
                'cancel' => $booking->isCancellable(),
                'reschedule' => in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING], true),
                'complete' => in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING], true),
                'review' => $booking->status === Booking::STATUS_COMPLETED && ! $booking->relationLoaded('review'),
            ],
        ];

        if ($detail) {
            $payload['subtotal'] = (float) $booking->subtotal;
            $payload['travel_charge'] = (float) $booking->travel_charge;
            $payload['discount_amount'] = (float) $booking->discount_amount;
            $payload['tax_amount'] = (float) $booking->tax_amount;
            $payload['payments'] = $booking->relationLoaded('payments') ? $booking->payments : [];
            $payload['attendance'] = $booking->relationLoaded('attendance') ? $booking->attendance : null;
            $payload['status_history'] = $booking->relationLoaded('statusHistories') ? $booking->statusHistories : [];
            $payload['online_session'] = $booking->relationLoaded('onlineSession') ? $booking->onlineSession : null;
        }

        return $payload;
    }

    protected function authorizeBooking(Booking $booking): void
    {
        abort_unless($booking->parent_profile_id === $this->parentOrFail()->id, 403, 'You are not allowed to access this booking.');
    }

    protected function authorizeStudent(StudentProfile $student): void
    {
        abort_unless($student->parent_profile_id === $this->parentOrFail()->id, 403, 'You are not allowed to access this child profile.');
    }

    protected function authorizePayment(Payment $payment): void
    {
        abort_unless($payment->parent_profile_id === $this->parentOrFail()->id, 403, 'You are not allowed to access this payment.');
    }

    protected function authorizeReview(Review $review): void
    {
        abort_unless($review->parent_profile_id === $this->parentOrFail()->id, 403, 'You are not allowed to modify this review.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function studentAttributes(array $validated): array
    {
        return [
            'display_name' => $validated['display_name'],
            'class_standard_id' => $validated['class_standard_id'] ?? null,
            'board_id' => $validated['board_id'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'school_name' => $validated['school_name'] ?? null,
            'learning_requirements' => $validated['learning_requirements'] ?? null,
            'subject_interests' => $validated['subject_interests'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function studentRules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'school_name' => ['nullable', 'string', 'max:180'],
            'learning_requirements' => ['nullable', 'string', 'max:2000'],
            'subject_interests' => ['nullable', 'array'],
            'subject_interests.*' => ['integer', 'exists:subjects,id'],
        ];
    }

    protected function recalculateTeacherRating(int $teacherProfileId): void
    {
        $teacher = TeacherProfile::find($teacherProfileId);

        if (! $teacher) {
            return;
        }

        $stats = Review::where('teacher_profile_id', $teacherProfileId)
            ->where('status', 'published')
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        $teacher->update([
            'rating_average' => round((float) ($stats->average ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ]);
    }
}
