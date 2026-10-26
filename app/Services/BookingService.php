<?php

namespace App\Services;

use App\Exceptions\BookingStateException;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\SlotHold;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Support\Platform;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orchestrates the booking lifecycle and keeps an auditable status history.
 */
class BookingService
{
    public function __construct(
        private readonly SlotService $slots,
        private readonly AvailabilityService $availability,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Create a booking in `pending_payment` state together with a temporary
     * hold on the teacher's slot.
     */
    public function create(
        ParentProfile $parent,
        StudentProfile $student,
        TeacherProfile $teacher,
        array $attributes,
        array $quote,
        ?SlotHold $existingHold = null
    ): Booking {
        return DB::transaction(function () use ($parent, $student, $teacher, $attributes, $quote, $existingHold) {
            $hold = $existingHold ?: $this->slots->hold(
                $teacher,
                $parent,
                $student,
                $attributes['start_at'],
                $attributes['end_at'],
                $attributes['mode'],
                request()->ip()
            );

            $booking = Booking::create([
                'reference_no' => $this->referenceNumber(),
                'student_profile_id' => $student->id,
                'parent_profile_id' => $parent->id,
                'teacher_profile_id' => $teacher->id,
                'board_id' => $attributes['board_id'] ?? $student->board_id,
                'class_standard_id' => $attributes['class_standard_id'] ?? $student->class_standard_id,
                'subject_id' => $attributes['subject_id'] ?? null,
                'teacher_location_id' => $attributes['teacher_location_id'] ?? null,
                'mode' => $attributes['mode'],
                'start_at' => $attributes['start_at'],
                'end_at' => $attributes['end_at'],
                'duration_minutes' => $attributes['duration_minutes'],
                'hourly_rate' => $quote['hourly_rate'],
                'subtotal' => $quote['subtotal'],
                'travel_charge' => $quote['travel_charge'],
                'discount_amount' => $quote['discount_amount'],
                'discount_code' => $quote['discount_code'],
                'tax_amount' => $quote['tax_amount'],
                'total_amount' => $quote['total_amount'],
                'currency' => $quote['currency'] ?? 'INR',
                'status' => Booking::STATUS_PENDING_PAYMENT,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'pricing_snapshot' => json_encode([
                    'hourly_rate' => $quote['hourly_rate'],
                    'subtotal' => $quote['subtotal'],
                    'travel_charge' => $quote['travel_charge'],
                    'discount' => $quote['discount_amount'],
                    'tax' => $quote['tax_amount'],
                    'total' => $quote['total_amount'],
                    'commission_percent' => Platform::get('commission_percent'),
                    'quoted_at' => now()->toIso8601String(),
                ]),
                'notes' => $attributes['notes'] ?? null,
                'is_recurring' => $attributes['is_recurring'] ?? false,
                'recurrence_count' => $attributes['recurrence_count'] ?? 1,
            ]);

            $this->slots->convertHold($hold, $booking);

            $this->history($booking, null, Booking::STATUS_PENDING_PAYMENT, 'Booking created; slot held.');

            return $booking->fresh();
        });
    }

    /**
     * Confirm a booking once the payment has been verified server-side.
     */
    public function confirm(Booking $booking, Payment $payment): Booking
    {
        return DB::transaction(function () use ($booking, $payment) {
            $requiresApproval = (bool) Platform::get('booking_requires_teacher_approval', false);

            $booking->update([
                'status' => $requiresApproval ? Booking::STATUS_PENDING_TEACHER : Booking::STATUS_CONFIRMED,
                'payment_status' => Booking::PAYMENT_PAID,
                'confirmed_at' => now(),
            ]);

            $this->history(
                $booking,
                $booking->getOriginal('status'),
                $booking->status,
                $requiresApproval
                    ? 'Payment verified; awaiting teacher acceptance.'
                    : 'Payment verified; booking confirmed.'
            );

            $this->createOnlineSessionIfNeeded($booking);

            $this->notifications->notify(
                $booking->teacher->user,
                'booking.confirmed',
                'New booking '.$booking->reference_no,
                $requiresApproval
                    ? 'A parent booked one of your slots. Please accept or decline the request.'
                    : 'A parent booked one of your slots on '.$booking->start_at->format('d M Y, h:i A').'.',
                ['booking_id' => $booking->id]
            );

            $this->notifications->notify(
                $booking->parent->user,
                'booking.confirmed',
                'Booking '.$booking->reference_no.' confirmed',
                'Your booking with '.$booking->teacher->user->name.' is confirmed for '.$booking->start_at->format('d M Y, h:i A').'.',
                ['booking_id' => $booking->id]
            );

            return $booking->fresh();
        });
    }

    /**
     * Cancel a booking and free the slot again when policy allows.
     */
    public function cancel(Booking $booking, string $reason, string $actorRole): Booking
    {
        if (! $booking->isCancellable()) {
            throw new BookingStateException('This booking can no longer be cancelled.');
        }

        return DB::transaction(function () use ($booking, $reason, $actorRole) {
            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancelled_by' => $actorRole,
                'cancelled_at' => now(),
            ]);

            $this->slots->releaseForBooking($booking);

            $this->history($booking, $booking->getOriginal('status'), Booking::STATUS_CANCELLED, 'Cancelled: '.$reason);

            $counterparty = $actorRole === 'teacher'
                ? $booking->parent->user
                : $booking->teacher->user;

            $this->notifications->notify(
                $counterparty,
                'booking.cancelled',
                'Booking '.$booking->reference_no.' cancelled',
                'The booking on '.$booking->start_at->format('d M Y, h:i A').' was cancelled. Reason: '.$reason,
                ['booking_id' => $booking->id]
            );

            return $booking->fresh();
        });
    }

    /**
     * Move a booking to a new time slot. The old slot is released and the
     * new one is reserved, preserving the original agreed price.
     */
    public function reschedule(Booking $booking, CarbonInterface $newStart, CarbonInterface $newEnd): Booking
    {
        if (! in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_PENDING_TEACHER], true)) {
            throw new BookingStateException('Only confirmed bookings can be rescheduled.');
        }

        $windowHours = (int) Platform::get('reschedule_window_hours', 12);

        if ($booking->start_at->diffInHours(now()) < $windowHours) {
            throw new BookingStateException(
                "Bookings can only be rescheduled more than {$windowHours} hour(s) before the scheduled class."
            );
        }

        return DB::transaction(function () use ($booking, $newStart, $newEnd) {
            $teacher = $booking->teacher;

            $check = $this->availability->isIntervalBookable($teacher, $newStart, $newEnd, $booking->mode, $booking->id);

            if (! $check['ok']) {
                throw new BookingStateException($check['reason']);
            }

            $this->slots->releaseForBooking($booking);
            $this->slots->insertReservations(
                $teacher->id,
                $newStart,
                $newEnd,
                null,
                null,
                $booking->id,
                \App\Models\SlotReservation::TYPE_BOOKING
            );

            $oldStart = $booking->start_at;

            $booking->update([
                'start_at' => $newStart,
                'end_at' => $newEnd,
                'duration_minutes' => $newStart->diffInMinutes($newEnd),
            ]);

            $this->history(
                $booking,
                $booking->status,
                $booking->status,
                'Rescheduled from '.$oldStart->format('d M Y, h:i A').' to '.$newStart->format('d M Y, h:i A')
            );

            $this->notifications->notify(
                $booking->parent->user,
                'booking.rescheduled',
                'Booking '.$booking->reference_no.' rescheduled',
                'Your class has been moved to '.$newStart->format('d M Y, h:i A').'.',
                ['booking_id' => $booking->id]
            );

            $this->notifications->notify(
                $booking->teacher->user,
                'booking.rescheduled',
                'Booking '.$booking->reference_no.' rescheduled',
                'A class has been moved to '.$newStart->format('d M Y, h:i A').'.',
                ['booking_id' => $booking->id]
            );

            return $booking->fresh();
        });
    }

    /**
     * Mark a session as completed and credit the teacher's earnings.
     */
    public function complete(Booking $booking, string $confirmedBy): Booking
    {
        if (! in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING], true)) {
            throw new BookingStateException('Only confirmed sessions can be completed.');
        }

        return DB::transaction(function () use ($booking, $confirmedBy) {
            $booking->update([
                'status' => Booking::STATUS_COMPLETED,
                'completed_at' => now(),
                'completion_confirmed_by' => $confirmedBy,
            ]);

            $this->history($booking, $booking->getOriginal('status'), Booking::STATUS_COMPLETED, 'Session completed.');

            $booking->teacher->increment('completed_sessions_count');

            $payment = $booking->payments()->where('status', Payment::STATUS_PAID)->latest()->first();

            if ($payment) {
                app(CommissionService::class)->record($booking, $payment);
                app(EarningService::class)->credit($booking, $payment);
            }

            $this->notifications->notify(
                $booking->parent->user,
                'booking.completed',
                'Session completed',
                'Your session with '.$booking->teacher->user->name.' is complete. Please rate your teacher.',
                ['booking_id' => $booking->id]
            );

            return $booking->fresh();
        });
    }

    /**
     * Record an auditable status transition.
     */
    public function history(Booking $booking, ?string $from, string $to, ?string $note = null, ?int $actorId = null): void
    {
        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actorId ?? auth()->id(),
            'actor_role' => auth()->user()?->role,
            'note' => $note,
        ]);
    }

    /**
     * Create the online class record (without a fabricated meeting link).
     */
    private function createOnlineSessionIfNeeded(Booking $booking): void
    {
        if ($booking->mode !== 'online') {
            return;
        }

        $booking->onlineSession()->firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'provider' => $booking->teacher->meeting_provider ?: 'manual',
                'scheduled_start' => $booking->start_at,
                'scheduled_end' => $booking->end_at,
                'status' => 'scheduled',
            ]
        );
    }

    private function referenceNumber(): string
    {
        do {
            $reference = 'EB'.now()->format('ymd').strtoupper(Str::random(5));
        } while (Booking::where('reference_no', $reference)->exists());

        return $reference;
    }
}
