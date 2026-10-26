<?php

namespace App\Http\Controllers\Api;

use App\Models\AttendanceRecord;
use App\Models\Booking;
use App\Models\ClassPackage;
use App\Models\Payment;
use App\Models\TeacherAvailabilityRule;
use App\Models\TeacherEarning;
use App\Models\TeacherFee;
use App\Models\TeacherLocation;
use App\Models\TeacherTimeSlot;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\EarningService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends ApiController
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly NotificationService $notifications,
        private readonly AvailabilityService $availability,
        private readonly EarningService $earnings,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $now = now();
        $upcomingStatuses = [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING];

        $todayBookings = Booking::with(['student', 'subject'])
            ->where('teacher_profile_id', $teacher->id)
            ->whereBetween('start_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->orderBy('start_at')
            ->get();

        $upcoming = Booking::with(['student', 'subject'])
            ->where('teacher_profile_id', $teacher->id)
            ->whereIn('status', $upcomingStatuses)
            ->where('start_at', '>=', $now)
            ->orderBy('start_at')
            ->limit(10)
            ->get();

        $availableSlots = 0;
        for ($day = $now->copy()->startOfDay(); $day->lte($now->copy()->addDays(6)->endOfDay()); $day->addDay()) {
            $availableSlots += $this->availability->slotsForDate($teacher, $day)
                ->where('status', AvailabilityService::STATUS_AVAILABLE)
                ->count();
        }

        return $this->ok([
            'teacher' => [
                'id' => $teacher->id,
                'name' => $request->user()->name,
                'headline' => $teacher->headline,
                'verification_status' => $teacher->verification_status,
                'rating_average' => (float) $teacher->rating_average,
                'rating_count' => (int) $teacher->rating_count,
                'city' => $teacher->city,
            ],
            'stats' => [
                'upcoming_count' => Booking::where('teacher_profile_id', $teacher->id)
                    ->whereIn('status', $upcomingStatuses)->where('start_at', '>=', $now)->count(),
                'pending_requests' => Booking::where('teacher_profile_id', $teacher->id)
                    ->where('status', Booking::STATUS_PENDING_TEACHER)->count(),
                'month_earnings' => (float) TeacherEarning::where('teacher_profile_id', $teacher->id)
                    ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                    ->sum('net_amount'),
                'completed_sessions' => Booking::where('teacher_profile_id', $teacher->id)
                    ->where('status', Booking::STATUS_COMPLETED)->count(),
                'available_slots' => $availableSlots,
                'unread_notifications' => \App\Models\AppNotification::where('user_id', $request->user()->id)
                    ->whereNull('read_at')->count(),
            ],
            'earnings' => $this->earnings->summary($teacher),
            'today_bookings' => $todayBookings->map(fn (Booking $b) => $this->bookingPayload($b)),
            'upcoming_bookings' => $upcoming->map(fn (Booking $b) => $this->bookingPayload($b)),
            'notifications' => \App\Models\AppNotification::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')->limit(10)->get(),
        ]);
    }

    // ---------------------------------------------------------------- profile

    public function profile(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $teacher->load([
            'user:id,name,email,mobile,avatar_path',
            'teacherSubjects.subject',
            'teacherBoards.board',
            'teacherClassStandards.classStandard',
            'teachingModes',
            'qualifications',
        ]);

        return $this->ok([
            'profile' => $teacher,
            'catalog' => $this->catalog(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'teaching_description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'methodology' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'experience_years' => ['sometimes', 'integer', 'min:0', 'max:80'],
            'languages' => ['sometimes', 'array'],
            'languages.*' => ['string', 'max:40'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'pincode' => ['sometimes', 'nullable', 'string', 'max:12'],
            'default_session_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'accepts_online' => ['sometimes', 'boolean'],
            'accepts_offline' => ['sometimes', 'boolean'],
            'cancellation_policy' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if (array_key_exists('name', $validated)) {
            $request->user()->update(['name' => $validated['name']]);
            unset($validated['name']);
        }

        $teacher->update($validated);

        return $this->ok(['profile' => $teacher->fresh()], 'Profile updated.');
    }

    // --------------------------------------------------------------- bookings

    public function bookings(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $tab = (string) $request->query('tab', 'upcoming');
        $now = now();

        $query = Booking::query()
            ->where('teacher_profile_id', $teacher->id)
            ->with(['student', 'subject', 'classStandard', 'board', 'location', 'parent.user']);

        match ($tab) {
            'upcoming' => $query->whereIn('status', [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
                ->where('start_at', '>=', $now),
            'pending' => $query->where('status', Booking::STATUS_PENDING_TEACHER),
            'completed' => $query->where('status', Booking::STATUS_COMPLETED),
            'cancelled' => $query->whereIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED, Booking::STATUS_DISPUTED]),
            default => null,
        };

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('reference_no', 'like', '%'.$search.'%');
        }

        $paginator = $query->orderByDesc('start_at')->paginate($this->perPage());

        $counts = [
            'upcoming' => Booking::where('teacher_profile_id', $teacher->id)
                ->whereIn('status', [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
                ->where('start_at', '>=', $now)->count(),
            'pending' => Booking::where('teacher_profile_id', $teacher->id)->where('status', Booking::STATUS_PENDING_TEACHER)->count(),
            'completed' => Booking::where('teacher_profile_id', $teacher->id)->where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled' => Booking::where('teacher_profile_id', $teacher->id)
                ->whereIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED, Booking::STATUS_DISPUTED])->count(),
            'all' => Booking::where('teacher_profile_id', $teacher->id)->count(),
        ];

        return $this->ok([
            'items' => collect($paginator->items())->map(fn (Booking $b) => $this->bookingPayload($b))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'counts' => $counts,
            'tab' => $tab,
        ]);
    }

    public function booking(Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        $booking->load([
            'student', 'parent.user', 'subject', 'classStandard', 'board', 'location',
            'payments', 'attendance', 'statusHistories', 'onlineSession', 'earnings', 'review',
        ]);

        $contactVisible = in_array($booking->status, [
            Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING, Booking::STATUS_COMPLETED,
        ], true);

        return $this->ok([
            'booking' => $this->bookingPayload($booking, true, $contactVisible),
        ]);
    }

    public function accept(Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        if ($booking->status !== Booking::STATUS_PENDING_TEACHER) {
            return $this->error('This booking is not waiting for your acceptance.', 422);
        }

        $booking->update(['status' => Booking::STATUS_CONFIRMED]);
        $this->bookings->history($booking, Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, 'Accepted by the teacher.');

        if ($booking->parent?->user) {
            $this->notifications->notify(
                $booking->parent->user,
                'booking.confirmed',
                'Booking '.$booking->reference_no.' accepted',
                'Your teacher accepted the class on '.$booking->start_at->format('d M Y, h:i A').'.',
                ['booking_id' => $booking->id]
            );
        }

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'The booking has been accepted. The parent has been notified.');
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        if (! in_array($booking->status, [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED], true)) {
            return $this->error('This booking can no longer be declined.', 422);
        }

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $reason = trim((string) ($validated['reason'] ?? '')) ?: 'Declined by the teacher';

        $this->bookings->cancel($booking, $reason, 'teacher');
        $refund = $this->refundPaidAmount($booking, 'Booking declined by the teacher: '.$reason);

        return $this->ok(
            ['booking' => $this->bookingPayload($booking->fresh()), 'refunded' => $refund],
            $refund ? 'Booking declined and a refund of '.$refund.' processed.' : 'Booking declined and the slot released.'
        );
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        if (! $booking->isCancellable()) {
            return $this->error('This booking can no longer be cancelled.', 422);
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->bookings->cancel($booking, $validated['reason'], 'teacher');
        $refund = $this->refundPaidAmount($booking, 'Booking cancelled by the teacher: '.$validated['reason']);

        return $this->ok(
            ['booking' => $this->bookingPayload($booking->fresh()), 'refunded' => $refund],
            $refund ? 'Booking cancelled and a refund of '.$refund.' processed.' : 'Booking cancelled and the slot released.'
        );
    }

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $start = Carbon::parse($validated['start_at']);
        $end = Carbon::parse($validated['end_at']);

        if ($start->lt(now())) {
            return $this->error('The new start time must be in the future.', 422);
        }

        try {
            $this->bookings->reschedule($booking, $start, $end);
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'The booking has been moved. The agreed price is unchanged.');
    }

    public function complete(Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        try {
            $this->bookings->complete($booking, 'teacher');
        } catch (\Throwable $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'The session has been marked as completed.');
    }

    public function attendance(Request $request, Booking $booking): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($booking->teacher_profile_id === $teacher->id, 403, 'This booking does not belong to you.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(['present', 'absent', 'late'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        AttendanceRecord::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'status' => $validated['status'],
                'marked_by' => $request->user()->id,
                'note' => $validated['note'] ?? null,
                'marked_at' => now(),
            ]
        );

        $booking->update(['attendance_status' => $validated['status']]);
        $this->bookings->history($booking, $booking->status, $booking->status, 'Attendance marked as '.$validated['status'].' by the teacher.');

        return $this->ok(['booking' => $this->bookingPayload($booking->fresh())], 'Attendance recorded.');
    }

    // ----------------------------------------------------------- availability

    public function availability(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        $rules = $teacher->availabilityRules()->orderBy('weekday')->orderBy('start_time')->get();
        $slots = $teacher->timeSlots()
            ->where('slot_date', '>=', now()->toDateString())
            ->orderBy('slot_date')->orderBy('start_time')
            ->limit(100)->get();

        return $this->ok([
            'rules' => $rules,
            'slots' => $slots,
            'weekdays' => TeacherAvailabilityRule::WEEKDAYS,
            'granularity_minutes' => $this->availability->granularityMinutes(),
            'calendar' => $this->calendarData($teacher, $request),
        ]);
    }

    public function storeRule(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $validated = $this->validateRule($request);

        $rule = $teacher->availabilityRules()->create($this->ruleAttributes($validated));

        return $this->ok(['rule' => $rule], 'The weekly availability rule has been added.', 201);
    }

    public function updateRule(Request $request, TeacherAvailabilityRule $rule): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($rule->teacher_profile_id === $teacher->id, 403, 'You may only edit your own availability rules.');

        $rule->update($this->ruleAttributes($this->validateRule($request)));

        return $this->ok(['rule' => $rule->fresh()], 'The weekly availability rule has been updated.');
    }

    public function destroyRule(TeacherAvailabilityRule $rule): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($rule->teacher_profile_id === $teacher->id, 403, 'You may only remove your own availability rules.');

        $rule->delete();

        return $this->ok(null, 'The availability rule has been removed.');
    }

    public function storeSlot(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $validated = $this->validateSlot($request);

        $slot = $teacher->timeSlots()->create($this->slotAttributes($validated));

        return $this->ok(['slot' => $slot], 'The one-off slot has been added.', 201);
    }

    public function updateSlot(Request $request, TeacherTimeSlot $slot): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($slot->teacher_profile_id === $teacher->id, 403, 'You may only edit your own one-off slots.');

        $slot->update($this->slotAttributes($this->validateSlot($request)));

        return $this->ok(['slot' => $slot->fresh()], 'The one-off slot has been updated.');
    }

    public function destroySlot(TeacherTimeSlot $slot): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($slot->teacher_profile_id === $teacher->id, 403, 'You may only remove your own one-off slots.');

        $slot->delete();

        return $this->ok(null, 'The one-off slot has been removed.');
    }

    public function calendar(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        return $this->ok($this->calendarData($teacher, $request));
    }

    // ------------------------------------------------------------------ fees

    public function fees(): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        return $this->ok([
            'fees' => $teacher->fees()->with(['subject', 'classStandard', 'board'])->latest()->get(),
            'packages' => $teacher->hasMany(ClassPackage::class, 'teacher_profile_id')->latest()->get(),
            'catalog' => $this->catalog(),
        ]);
    }

    public function storeFee(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $validated = $request->validate($this->feeRules());

        $fee = $teacher->fees()->create($validated + ['is_active' => true]);

        return $this->ok(['fee' => $fee->load(['subject', 'classStandard', 'board'])], 'Teaching fee saved.', 201);
    }

    public function updateFee(Request $request, TeacherFee $fee): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($fee->teacher_profile_id === $teacher->id, 403, 'You may only edit your own fees.');

        $fee->update($request->validate($this->feeRules(true)));

        return $this->ok(['fee' => $fee->fresh()->load(['subject', 'classStandard', 'board'])], 'Teaching fee updated.');
    }

    public function destroyFee(TeacherFee $fee): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($fee->teacher_profile_id === $teacher->id, 403, 'You may only remove your own fees.');

        $fee->delete();

        return $this->ok(null, 'Teaching fee removed.');
    }

    public function storePackage(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'mode' => ['required', Rule::in(['online', 'offline', 'both'])],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:200'],
            'session_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'price_per_session' => ['required', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $validated['total_price'] = $validated['total_price']
            ?? round((float) $validated['price_per_session'] * (int) $validated['total_sessions'], 2);
        $validated['teacher_profile_id'] = $teacher->id;
        $validated['is_active'] = true;

        $package = ClassPackage::create($validated);

        return $this->ok(['package' => $package], 'Class package created.', 201);
    }

    public function destroyPackage(ClassPackage $package): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($package->teacher_profile_id === $teacher->id, 403, 'You may only remove your own packages.');

        $package->delete();

        return $this->ok(null, 'Class package removed.');
    }

    // -------------------------------------------------------------- earnings

    public function earnings(): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        return $this->ok([
            'summary' => $this->earnings->summary($teacher),
            'available_balance' => (float) $this->earnings->availableEarnings($teacher)->sum('net_amount'),
            'earnings' => TeacherEarning::with(['booking.student', 'booking.subject'])
                ->where('teacher_profile_id', $teacher->id)
                ->orderByDesc('created_at')->limit(100)->get(),
            'payouts' => $teacher->payouts()->orderByDesc('created_at')->limit(50)->get(),
        ]);
    }

    public function earning(TeacherEarning $earning): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($earning->teacher_profile_id === $teacher->id, 403, 'This earning does not belong to you.');

        $earning->load(['booking.student', 'booking.subject', 'payout']);

        return $this->ok(['earning' => $earning]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:1'],
            'method' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payout = $this->earnings->createPayout(
                $teacher,
                isset($validated['amount']) ? (float) $validated['amount'] : null,
                $validated['method'] ?? 'bank_transfer',
                $validated['notes'] ?? null,
                null
            );
        } catch (\RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->ok(['payout' => $payout], 'Payout request submitted.', 201);
    }

    // ------------------------------------------------------------- locations

    public function locations(): JsonResponse
    {
        $teacher = $this->teacherOrFail();

        return $this->ok(['locations' => $teacher->locations()->latest()->get()]);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        $validated = $this->validateLocation($request);
        $validated['teacher_profile_id'] = $teacher->id;

        if (! empty($validated['is_default'])) {
            $teacher->locations()->update(['is_default' => false]);
        }

        $location = TeacherLocation::create($validated);

        return $this->ok(['location' => $location], 'Teaching location saved.', 201);
    }

    public function updateLocation(Request $request, TeacherLocation $location): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($location->teacher_profile_id === $teacher->id, 403, 'You may only edit your own locations.');

        $validated = $this->validateLocation($request, true);

        if (! empty($validated['is_default'])) {
            $teacher->locations()->where('id', '!=', $location->id)->update(['is_default' => false]);
        }

        $location->update($validated);

        return $this->ok(['location' => $location->fresh()], 'Teaching location updated.');
    }

    public function destroyLocation(TeacherLocation $location): JsonResponse
    {
        $teacher = $this->teacherOrFail();
        abort_unless($location->teacher_profile_id === $teacher->id, 403, 'You may only remove your own locations.');

        $location->delete();

        return $this->ok(null, 'Teaching location removed.');
    }

    // ---------------------------------------------------------------- helpers

    /**
     * @return array<string, mixed>
     */
    protected function bookingPayload(Booking $booking, bool $detail = false, bool $contactVisible = false): array
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
            'cancel_reason' => $booking->cancel_reason,
            'student' => $booking->student ? [
                'id' => $booking->student->id,
                'name' => $booking->student->display_name,
            ] : null,
            'subject' => $booking->subject ? ['id' => $booking->subject->id, 'name' => $booking->subject->name] : null,
            'class_standard' => $booking->classStandard ? ['id' => $booking->classStandard->id, 'name' => $booking->classStandard->name] : null,
            'board' => $booking->board ? ['id' => $booking->board->id, 'name' => $booking->board->name] : null,
            'location' => $booking->location ? [
                'id' => $booking->location->id,
                'label' => $booking->location->label,
                'city' => $booking->location->city,
            ] : null,
            'parent' => $booking->relationLoaded('parent') && $booking->parent ? [
                'id' => $booking->parent->id,
                'name' => $booking->parent->user?->name,
                'mobile' => $contactVisible ? $booking->parent->user?->mobile : null,
                'email' => $contactVisible ? $booking->parent->user?->email : null,
            ] : null,
            'can' => [
                'accept' => $booking->status === Booking::STATUS_PENDING_TEACHER,
                'reject' => in_array($booking->status, [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED], true),
                'cancel' => $booking->isCancellable(),
                'reschedule' => in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING], true),
                'complete' => in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING], true),
                'attendance' => in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING, Booking::STATUS_COMPLETED], true),
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
            $payload['review'] = $booking->relationLoaded('review') ? $booking->review : null;
        }

        return $payload;
    }

    protected function refundPaidAmount(Booking $booking, string $reason): ?float
    {
        $payment = $booking->payments()->where('status', Payment::STATUS_PAID)->latest()->first();

        if (! $payment) {
            return null;
        }

        $refundable = $payment->refundableAmount();

        if ($refundable <= 0.01) {
            return null;
        }

        $amount = min((float) $payment->amount, $refundable);
        $this->payments->refund($payment, round($amount, 2), $reason, request()->user()?->id);

        return round($amount, 2);
    }

    /**
     * @return array<string, mixed>
     */
    protected function calendarData(\App\Models\TeacherProfile $teacher, Request $request): array
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $start = now()->startOfMonth();
        }

        $summary = $this->availability->monthSummary($teacher, (int) $start->year, (int) $start->month);
        $days = [];

        for ($date = $start->copy(); $date->lte($start->copy()->endOfMonth()); $date->addDay()) {
            $key = $date->toDateString();
            $row = $summary[$key] ?? ['available_slots' => 0, 'total_slots' => 0, 'is_available' => false];

            $days[] = [
                'date' => $key,
                'day' => (int) $date->day,
                'weekday' => $date->dayOfWeek,
                'is_today' => $date->isSameDay(now()),
                'is_past' => $date->endOfDay()->isPast(),
                'total_slots' => (int) $row['total_slots'],
                'available_slots' => (int) $row['available_slots'],
                'is_available' => (bool) $row['is_available'],
            ];
        }

        return [
            'month' => $start->format('Y-m'),
            'label' => $start->format('F Y'),
            'granularity_minutes' => $this->availability->granularityMinutes(),
            'days' => $days,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function catalog(): array
    {
        return [
            'subjects' => \App\Models\Subject::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'code']),
            'boards' => \App\Models\Board::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'code']),
            'classes' => \App\Models\ClassStandard::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'code']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRule(Request $request): array
    {
        return $request->validate([
            'weekday' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'mode' => ['required', Rule::in(['both', 'online', 'offline'])],
            'session_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function ruleAttributes(array $validated): array
    {
        return [
            'weekday' => (int) $validated['weekday'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'mode' => $validated['mode'],
            'session_minutes' => (int) $validated['session_minutes'],
            'buffer_minutes' => (int) ($validated['buffer_minutes'] ?? 0),
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateSlot(Request $request): array
    {
        return $request->validate([
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['available', 'blocked', 'holiday', 'custom'])],
            'mode' => ['required', Rule::in(['both', 'online', 'offline'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function slotAttributes(array $validated): array
    {
        return [
            'slot_date' => $validated['slot_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'],
            'mode' => $validated['mode'],
            'buffer_minutes' => 0,
            'note' => $validated['note'] ?? null,
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function feeRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'mode' => [$required, Rule::in(['online', 'offline', 'both'])],
            'hourly_rate' => [$required, 'numeric', 'min:0', 'max:100000'],
            'session_minutes' => [$required, 'integer', 'min:15', 'max:480'],
            'travel_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateLocation(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'label' => [$required, 'string', 'max:120'],
            'type' => ['sometimes', Rule::in(['teacher_location', 'home', 'institute', 'online'])],
            'address_line1' => [$required, 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:120'],
            'city' => [$required, 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'travel_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
