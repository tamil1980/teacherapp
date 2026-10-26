<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingStateException;
use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TeacherProfile;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Booking oversight: filters, full detail and administrator overrides.
 */
class AdminBookingController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Statuses an administrator may force a booking into.
     *
     * `pending_payment` and `completed` are deliberately excluded: the first
     * is owned by the checkout flow and the second credits earnings, which
     * must go through the dedicated "mark completed" action.
     *
     * @return array<int, string>
     */
    public static function overridableStatuses(): array
    {
        return [
            Booking::STATUS_PENDING_TEACHER,
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_ONGOING,
            Booking::STATUS_DISPUTED,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_REFUNDED,
        ];
    }

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'mode' => (string) $request->query('mode', ''),
            'teacher' => (string) $request->query('teacher', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $bookings = Booking::query()
            ->with(['teacher.user', 'parent.user', 'student', 'subject', 'board', 'classStandard'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when(in_array($filters['mode'], ['online', 'offline'], true), fn ($query) => $query->where('mode', $filters['mode']))
            ->when($filters['teacher'] !== '', fn ($query) => $query->where('teacher_profile_id', (int) $filters['teacher']))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('start_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('start_at', '<=', $filters['to']))
            ->when($filters['q'] !== '', fn ($query) => $query->where('reference_no', 'like', '%'.$filters['q'].'%'))
            ->orderByDesc('start_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'teachers' => TeacherProfile::query()->with('user')->orderBy('id')->get(),
            'statusCounts' => Booking::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'teacher.user',
            'parent.user',
            'student.classStandard',
            'student.board',
            'subject',
            'board',
            'classStandard',
            'location',
            'statusHistories.actor',
            'payments',
            'commissions',
            'earnings',
            'refunds',
            'attendance',
            'review',
            'onlineSession',
        ]);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'payment' => $booking->payments->sortByDesc('created_at')->first(),
            'commission' => $booking->commissions,
            'earning' => $booking->earnings->first(),
            'overridableStatuses' => self::overridableStatuses(),
        ]);
    }

    /**
     * Force a booking into another status. The change is recorded in the
     * booking status history with the administrator's note.
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::overridableStatuses())],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'status.in' => 'That status cannot be set from the admin panel.',
        ]);

        $from = $booking->status;
        $to = $validated['status'];

        // Cancelling releases the reserved slot, so the booking service is
        // used when it allows the transition.
        if ($to === Booking::STATUS_CANCELLED) {
            try {
                $booking = $this->bookings->cancel($booking, $validated['note'], 'admin');

                $this->bookings->history(
                    $booking,
                    $from,
                    Booking::STATUS_CANCELLED,
                    'Administrator override: '.$validated['note']
                );
            } catch (BookingStateException) {
                $booking->update([
                    'status' => Booking::STATUS_CANCELLED,
                    'cancel_reason' => $validated['note'],
                    'cancelled_by' => 'admin',
                    'cancelled_at' => now(),
                ]);

                $this->bookings->history(
                    $booking,
                    $from,
                    Booking::STATUS_CANCELLED,
                    'Administrator override: '.$validated['note']
                );
            }
        } else {
            $attributes = ['status' => $to];

            if ($to === Booking::STATUS_CONFIRMED && $booking->confirmed_at === null) {
                $attributes['confirmed_at'] = now();
            }

            if ($to === Booking::STATUS_REFUNDED) {
                $attributes['payment_status'] = Booking::PAYMENT_REFUNDED;
            }

            $booking->update($attributes);

            $this->bookings->history(
                $booking,
                $from,
                $to,
                'Administrator override: '.$validated['note']
            );
        }

        $this->audit('booking.status.update', Booking::class, $booking->id, [
            'reference_no' => $booking->reference_no,
            'from' => $from,
            'to' => $to,
            'note' => $validated['note'],
        ]);

        return back()->with('success', 'Booking '.$booking->reference_no.' is now "'.$to.'".');
    }

    /**
     * Mark the session as completed and credit the teacher's earnings.
     */
    public function complete(Booking $booking): RedirectResponse
    {
        try {
            $booking = $this->bookings->complete($booking, 'admin');
        } catch (BookingStateException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->audit('booking.complete', Booking::class, $booking->id, [
            'reference_no' => $booking->reference_no,
            'teacher_profile_id' => $booking->teacher_profile_id,
            'total_amount' => (float) $booking->total_amount,
        ]);

        return back()->with('success', 'Booking '.$booking->reference_no.' has been completed and the earnings credited.');
    }
}
