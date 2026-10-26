<?php

namespace App\Http\Controllers\Teacher;

use App\Exceptions\BookingStateException;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The teacher's booking inbox: accept, decline, reschedule, complete.
 *
 * Every state transition goes through the BookingService so the status
 * history and the notifications stay consistent with the rest of the app.
 */
class TeacherBookingController extends Controller
{
    public const TABS = [
        'upcoming' => 'Upcoming',
        'pending' => 'Awaiting acceptance',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'all' => 'All bookings',
    ];

    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Booking list with tabs, filters and a reference search.
     */
    public function index(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $tab = (string) $request->query('tab', 'upcoming');

        if (! array_key_exists($tab, self::TABS)) {
            $tab = 'upcoming';
        }

        $query = Booking::query()
            ->where('teacher_profile_id', $teacher->id)
            ->with(['student', 'subject', 'classStandard', 'board', 'location']);

        $this->applyTab($query, $tab);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('reference_no', 'like', '%'.$search.'%');
        }

        $bookings = $query
            ->orderBy('start_at')
            ->paginate(15)
            ->withQueryString();

        return view('teacher.bookings.index', [
            'teacher' => $teacher,
            'bookings' => $bookings,
            'tab' => $tab,
            'tabs' => self::TABS,
            'counts' => $this->counts($teacher),
            'search' => $search ?? '',
        ]);
    }

    /**
     * Full booking detail.
     */
    public function show(Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        $booking->load([
            'student',
            'parent.user',
            'subject',
            'classStandard',
            'board',
            'location',
            'payments',
            'attendance',
            'statusHistories',
            'onlineSession',
            'earnings',
        ]);

        // The parent's contact details are only shared once the class is agreed.
        $contactVisible = in_array($booking->status, [
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_ONGOING,
            Booking::STATUS_COMPLETED,
        ], true);

        return view('teacher.bookings.show', [
            'teacher' => $teacher,
            'booking' => $booking,
            'contactVisible' => $contactVisible,
            'paidPayment' => $booking->payments()->where('status', Payment::STATUS_PAID)->latest()->first(),
        ]);
    }

    /**
     * Accept a booking that is waiting for the teacher's confirmation.
     */
    public function accept(Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        if ($booking->status !== Booking::STATUS_PENDING_TEACHER) {
            return back()->with('error', 'This booking is not waiting for your acceptance.');
        }

        $booking->update(['status' => Booking::STATUS_CONFIRMED]);

        $this->bookings->history(
            $booking,
            Booking::STATUS_PENDING_TEACHER,
            Booking::STATUS_CONFIRMED,
            'Accepted by the teacher.'
        );

        $this->notifications->notify(
            $booking->parent->user,
            'booking.confirmed',
            'Booking '.$booking->reference_no.' accepted',
            'Your teacher accepted the class on '.$booking->start_at->format('d M Y, h:i A').'.',
            ['booking_id' => $booking->id]
        );

        return back()->with('success', 'The booking has been accepted. The parent has been notified.');
    }

    /**
     * Decline a booking: cancel it, release the slot and refund the parent.
     */
    public function reject(Request $request, Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        if (! in_array($booking->status, [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED], true)) {
            return back()->with('error', 'This booking can no longer be declined.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? '')) ?: 'Declined by the teacher';

        // Cancelling releases the reserved slot and records the history.
        $this->bookings->cancel($booking, $reason, 'teacher');

        $refund = $this->refundPaidAmount($booking, 'Booking declined by the teacher: '.$reason);

        return back()->with('success', $refund
            ? 'The booking has been declined, the slot released and a refund of '.$this->money($refund).' processed.'
            : 'The booking has been declined and the slot released.');
    }

    /**
     * Cancel a confirmed booking (same refund flow as a rejection).
     */
    public function cancel(Request $request, Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        if (! $booking->isCancellable()) {
            return back()->with('error', 'This booking can no longer be cancelled.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $reason = $validated['reason'];

        $this->bookings->cancel($booking, $reason, 'teacher');

        $refund = $this->refundPaidAmount($booking, 'Booking cancelled by the teacher: '.$reason);

        return back()->with('success', $refund
            ? 'The booking has been cancelled and a refund of '.$this->money($refund).' processed.'
            : 'The booking has been cancelled and the slot released.');
    }

    /**
     * Move a booking to a new time slot.
     */
    public function reschedule(Request $request, Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $newStart = Carbon::parse($validated['start_at']);
        $newEnd = Carbon::parse($validated['end_at']);

        if ($newStart->lt(now())) {
            return back()->withInput()->withErrors(['start_at' => 'The new start time must be in the future.']);
        }

        try {
            $this->bookings->reschedule($booking, $newStart, $newEnd);
        } catch (BookingStateException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'The booking has been moved to '.$newStart->format('d M Y, h:i A').'. The agreed price is unchanged.');
    }

    /**
     * Mark a session as completed (this credits the teacher's earnings).
     */
    public function complete(Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        try {
            $this->bookings->complete($booking, 'teacher');
        } catch (BookingStateException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'The session has been marked as completed.');
    }

    /**
     * Record the attendance of a session.
     */
    public function attendance(Request $request, Booking $booking)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $booking->teacher_profile_id !== $teacher->id) {
            abort(403, 'This booking does not belong to you.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['present', 'absent', 'late'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $validated['status'];

        AttendanceRecord::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'status' => $status,
                'marked_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
                'marked_at' => now(),
            ]
        );

        $booking->update(['attendance_status' => $status]);

        $this->bookings->history(
            $booking,
            $booking->status,
            $booking->status,
            'Attendance marked as '.$status.' by the teacher.'
        );

        return back()->with('success', 'Attendance has been recorded as "'.$status.'".');
    }

    /**
     * Apply the selected tab to the booking query.
     */
    protected function applyTab($query, string $tab): void
    {
        $now = now();

        match ($tab) {
            'upcoming' => $query
                ->whereIn('status', [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
                ->where('start_at', '>=', $now),
            'pending' => $query->where('status', Booking::STATUS_PENDING_TEACHER),
            'completed' => $query->where('status', Booking::STATUS_COMPLETED),
            'cancelled' => $query->whereIn('status', [
                Booking::STATUS_CANCELLED,
                Booking::STATUS_REFUNDED,
                Booking::STATUS_DISPUTED,
            ]),
            default => $query,
        };
    }

    /**
     * Counts shown next to the tabs.
     *
     * @return array<string, int>
     */
    protected function counts($teacher): array
    {
        $now = now();

        return [
            'upcoming' => Booking::where('teacher_profile_id', $teacher->id)
                ->whereIn('status', [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING])
                ->where('start_at', '>=', $now)
                ->count(),
            'pending' => Booking::where('teacher_profile_id', $teacher->id)
                ->where('status', Booking::STATUS_PENDING_TEACHER)
                ->count(),
            'completed' => Booking::where('teacher_profile_id', $teacher->id)
                ->where('status', Booking::STATUS_COMPLETED)
                ->count(),
            'cancelled' => Booking::where('teacher_profile_id', $teacher->id)
                ->whereIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED, Booking::STATUS_DISPUTED])
                ->count(),
            'all' => Booking::where('teacher_profile_id', $teacher->id)->count(),
        ];
    }

    /**
     * Refund the paid amount of a booking, if there is one.
     *
     * @return float|null the amount that was refunded
     */
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

        $this->payments->refund($payment, round($amount, 2), $reason, auth()->id());

        return round($amount, 2);
    }
}
