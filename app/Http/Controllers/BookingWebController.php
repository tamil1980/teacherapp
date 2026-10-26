<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingStateException;
use App\Exceptions\PaymentException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Payment;
use App\Models\SlotHold;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\SlotService;
use App\Support\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingWebController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly SlotService $slots,
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Step 1 — pick a student, subject, mode, date and slot.
     */
    public function create(Request $request, TeacherProfile $teacher): View|RedirectResponse
    {
        abort_unless($teacher->isApproved(), 404);

        $parent = $this->parentProfile();

        if (! $parent) {
            return redirect()->route('login')->with('error', 'Please log in as a parent to book a class.');
        }

        $students = $parent->students()->with(['classStandard', 'board'])->get();

        if ($students->isEmpty()) {
            return redirect()->route('parent.students.create')
                ->with('warning', 'Add a child profile before booking your first class.');
        }

        $mode = in_array($request->string('mode')->value(), ['online', 'offline'], true)
            ? $request->string('mode')->value()
            : 'online';

        $date = $request->date('date') ?? now()->addDay();

        $slots = $this->availability->slotsForDate($teacher, $date, $mode);

        $subjects = $teacher->teacherSubjects()->with('subject')->get()->pluck('subject');
        $classStandards = $teacher->teacherClassStandards()->with('classStandard')->get()->pluck('classStandard');
        $boards = $teacher->teacherBoards()->with('board')->get()->pluck('board');
        $locations = $teacher->locations()->where('is_active', true)->get();

        $feeOptions = $teacher->fees()->with(['subject', 'classStandard', 'board'])->where('is_active', true)->get();

        return view('bookings.create', compact(
            'teacher',
            'students',
            'mode',
            'date',
            'slots',
            'subjects',
            'classStandards',
            'boards',
            'locations',
            'feeOptions'
        ));
    }

    /**
     * Step 2 — create the booking and hold the slot.
     */
    public function store(Request $request): RedirectResponse
    {
        $parent = $this->parentProfile();

        $validated = $request->validate([
            'teacher_profile_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'student_profile_id' => ['required', 'integer', 'exists:student_profiles,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'teacher_location_id' => ['nullable', 'integer', 'exists:teacher_locations,id'],
            'mode' => ['required', 'in:online,offline'],
            'start_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount_code' => ['nullable', 'string', 'max:40'],
        ]);

        $teacher = TeacherProfile::findOrFail($validated['teacher_profile_id']);
        abort_unless($teacher->isApproved(), 404);

        $student = StudentProfile::findOrFail($validated['student_profile_id']);
        abort_unless(
            $student->parent_profile_id === $parent->id,
            403,
            'You can only book for your own children.'
        );

        $start = \Carbon\Carbon::parse($validated['start_at']);
        $end = $start->copy()->addMinutes((int) $validated['duration_minutes']);

        // Re-verify availability on the server — never trust the browser.
        $check = $this->availability->isIntervalBookable($teacher, $start, $end, $validated['mode']);

        if (! $check['ok']) {
            return back()->with('error', $check['reason'])->withInput();
        }

        $subject = $validated['subject_id'] ? Subject::find($validated['subject_id']) : null;
        $classStandard = $validated['class_standard_id'] ? ClassStandard::find($validated['class_standard_id']) : null;
        $board = $validated['board_id'] ? Board::find($validated['board_id']) : null;

        $locationId = $validated['teacher_location_id'] ?? null;
        $location = $locationId ? $teacher->locations()->find($locationId) : null;

        $quote = $this->pricing->quote(
            $teacher,
            (int) $validated['duration_minutes'],
            $validated['mode'],
            $subject,
            $classStandard,
            $board,
            $validated['discount_code'] ?? null,
            $location ? (float) $location->travel_charge : null
        );

        if ($quote['hourly_rate'] <= 0) {
            return back()->with('error', 'This teacher has not set a fee for the selected combination yet.')->withInput();
        }

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
        } catch (SlotUnavailableException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('bookings.checkout', $booking);
    }

    /**
     * Step 3 — full price breakdown and cancellation policy.
     */
    public function checkout(Request $request, Booking $booking): View
    {
        $this->authorizeBookingOwner($booking);

        $booking->load(['teacher.user', 'student', 'subject', 'classStandard', 'board', 'location']);

        $quote = [
            'hourly_rate' => (float) $booking->hourly_rate,
            'duration_minutes' => (int) $booking->duration_minutes,
            'subtotal' => (float) $booking->subtotal,
            'travel_charge' => (float) $booking->travel_charge,
            'discount_amount' => (float) $booking->discount_amount,
            'tax_amount' => (float) $booking->tax_amount,
            'total_amount' => (float) $booking->total_amount,
        ];

        return view('bookings.checkout', [
            'booking' => $booking,
            'quote' => $quote,
            'cancellationPolicy' => Platform::get('refund_policy_text'),
            'cancellationWindow' => (int) Platform::get('cancellation_window_hours', 24),
            'cancellationFeePercent' => (float) Platform::get('cancellation_fee_percent', 0),
            'durationOptions' => Platform::get('session_duration_options', [30, 60, 90, 120, 180]),
        ]);
    }

    /**
     * Step 4 — the payment screen.
     */
    public function pay(Request $request, Booking $booking): View
    {
        $this->authorizeBookingOwner($booking);

        if ($booking->payment_status === Booking::PAYMENT_PAID) {
            return redirect()->route('bookings.result', $booking);
        }

        $payment = $this->payments->createOrder($booking, $this->parentProfile(), $request->user());

        $gateway = $this->payments->gateway();

        return view('bookings.pay', [
            'booking' => $booking->load(['teacher.user', 'student', 'subject']),
            'payment' => $payment,
            'checkoutConfig' => $gateway->checkoutConfig(
                new \App\Services\Payments\GatewayOrder(
                    $payment->gateway_order_id,
                    (float) $payment->amount,
                    $payment->currency,
                    (string) config('edubook.razorpay.key_id') ?: 'mock_public_key',
                    $payment->order_id,
                )
            ),
            'isMockGateway' => $gateway->name() === 'mock',
        ]);
    }

    /**
     * Step 5 — verify the payment server-side and confirm the booking.
     */
    public function processPayment(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBookingOwner($booking);

        $payment = $booking->payments()->where('status', Payment::STATUS_CREATED)->latest()->first();

        if (! $payment) {
            return redirect()->route('bookings.result', $booking);
        }

        $payload = [
            'razorpay_order_id' => $request->string('razorpay_order_id')->value(),
            'razorpay_payment_id' => $request->string('razorpay_payment_id')->value(),
            'razorpay_signature' => $request->string('razorpay_signature')->value(),
            'razorpay_method' => $request->string('razorpay_method')->value() ?: 'upi',
        ];

        try {
            $this->payments->capture($payment, $payload);
        } catch (PaymentException $e) {
            return redirect()->route('bookings.result', $booking)->with('error', $e->getMessage());
        }

        return redirect()->route('bookings.result', $booking)->with('success', 'Payment successful. Your booking is confirmed.');
    }

    /**
     * Step 6 — the result screen.
     */
    public function result(Request $request, Booking $booking): View
    {
        $this->authorizeBookingOwner($booking);

        $booking->load(['teacher.user', 'student', 'subject', 'classStandard', 'board', 'location', 'payments']);

        return view('bookings.result', compact('booking'));
    }

    /**
     * Release a temporary hold when the customer abandons checkout.
     */
    public function releaseHold(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBookingOwner($booking);

        $hold = SlotHold::where('booking_id', $booking->id)
            ->where('status', SlotHold::STATUS_CONVERTED)
            ->latest()
            ->first();

        // The booking already converted its hold into a reservation; releasing
        // means cancelling the unpaid booking and freeing the slot.
        if ($booking->payment_status === Booking::PAYMENT_UNPAID
            && in_array($booking->status, [Booking::STATUS_PENDING_PAYMENT], true)) {
            $this->bookings->cancel($booking, 'Checkout abandoned by the customer.', 'parent');
        }

        return response()->json(['released' => true]);
    }

    /**
     * Online class meeting link — visible only to the parent and the teacher
     * of a paid, confirmed booking.
     */
    public function meeting(Request $request, Booking $booking): View
    {
        $this->authorizeBookingOwner($booking);

        $session = $booking->onlineSession;

        return view('bookings.meeting', compact('booking', 'session'));
    }

    /**
     * Allow the authorised teacher to attach a meeting link.
     */
    public function storeMeetingLink(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        abort_unless(
            $user->role === 'admin' || ($user->role === 'teacher' && $booking->teacher_profile_id === $user->teacherProfile?->id),
            403
        );

        $validated = $request->validate([
            'join_url' => ['required', 'url', 'max:500'],
            'meeting_id' => ['nullable', 'string', 'max:120'],
            'passcode' => ['nullable', 'string', 'max:40'],
        ]);

        $session = $booking->onlineSession ?: $booking->onlineSession()->create([
            'provider' => $booking->teacher->meeting_provider ?: 'manual',
            'scheduled_start' => $booking->start_at,
            'scheduled_end' => $booking->end_at,
            'status' => 'scheduled',
        ]);

        $session->update([
            'join_url' => $validated['join_url'],
            'meeting_id' => $validated['meeting_id'] ?? null,
            'passcode' => $validated['passcode'] ?? null,
            'status' => 'link_shared',
        ]);

        return back()->with('success', 'Meeting link shared with the parent.');
    }

    /**
     * A parent may only act on their own bookings.
     */
    private function authorizeBookingOwner(Booking $booking): void
    {
        $user = request()->user();

        abort_unless(
            $user->role === 'admin' || $booking->parent_profile_id === $this->parentProfile()?->id,
            403,
            'You are not allowed to access this booking.'
        );
    }
}
