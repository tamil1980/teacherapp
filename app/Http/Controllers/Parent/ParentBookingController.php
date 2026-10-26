<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\SlotService;
use App\Support\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentBookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
        private readonly SlotService $slots,
    ) {}

    public function index(Request $request): View
    {
        $parent = $this->parentProfile();

        $query = Booking::where('parent_profile_id', $parent->id)
            ->with(['teacher.user', 'subject', 'classStandard', 'board', 'student']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        } elseif ($request->boolean('upcoming')) {
            $query->where('start_at', '>=', now())->whereNotIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED]);
        } elseif ($request->boolean('past')) {
            $query->where('start_at', '<', now());
        }

        $bookings = $query->orderByDesc('start_at')->paginate(15)->withQueryString();

        return view('parent.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View
    {
        $this->authorizeBooking($booking);

        $booking->load([
            'teacher.user', 'teacher.teacherSubjects.subject',
            'student', 'subject', 'classStandard', 'board', 'location',
            'payments', 'statusHistories.actor', 'onlineSession', 'attendance', 'review',
        ]);

        $rescheduleWindow = (int) Platform::get('reschedule_window_hours', 12);

        return view('parent.bookings.show', compact('booking', 'rescheduleWindow'));
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($booking);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $payment = $booking->payments()->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_REFUNDED])->latest()->first();

        $feePercent = (float) Platform::get('cancellation_fee_percent', 0);
        $windowHours = (int) Platform::get('cancellation_window_hours', 24);

        $refundAmount = 0.0;
        $cancellationFee = 0.0;

        if ($payment) {
            $gross = (float) $payment->amount;
            $hoursUntil = now()->diffInHours($booking->start_at, false);

            if ($hoursUntil < 0) {
                $hoursUntil = 0;
            }

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
                'Cancellation fee: '.$this->money($cancellationFee)
            );
        }

        return redirect()->route('parent.bookings.show', $booking)->with(
            'success',
            $payment && $refundAmount > 0
                ? 'Booking cancelled. A refund of '.$this->money($refundAmount).' has been initiated.'
                : 'Booking cancelled and the slot has been released.'
        );
    }

    public function reschedule(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($booking);

        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
        ]);

        $start = \Carbon\Carbon::parse($validated['start_at']);
        $end = $start->copy()->addMinutes((int) $validated['duration_minutes']);

        $this->bookings->reschedule($booking, $start, $end);

        return redirect()->route('parent.bookings.show', $booking)
            ->with('success', 'Your class has been rescheduled to '.$start->format('d M Y, h:i A').'.');
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($booking);

        $this->bookings->complete($booking, 'parent');

        return redirect()->route('parent.bookings.show', $booking)
            ->with('success', 'Session marked as completed. Please rate your teacher.');
    }

    public function attendance(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($booking);

        $validated = $request->validate([
            'status' => ['required', 'in:present,absent,late'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $booking->attendance()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'marked_by' => $request->user()->id,
                'marked_at' => now(),
            ]
        );

        $booking->update(['attendance_status' => $validated['status']]);

        return back()->with('success', 'Attendance recorded.');
    }
}
