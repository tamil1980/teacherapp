<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\ParentProfile;
use App\Models\SlotHold;
use App\Models\SlotReservation;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Support\Platform;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the slot lifecycle: temporary holds, confirmed reservations and
 * releases.
 *
 * Concurrency guarantees
 * ----------------------
 * 1. Every operation runs inside a database transaction.
 * 2. The teacher's profile row is locked with `SELECT ... FOR UPDATE`, which
 *    serialises concurrent attempts for the same teacher.
 * 3. The unique index `slot_reservations (teacher_profile_id, slot_start_at)`
 *    is the final, database-level guard: even if two requests somehow pass
 *    the checks at the same instant, only one can insert the reservation.
 */
class SlotService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Temporarily hold a slot while the customer completes checkout.
     */
    public function hold(
        TeacherProfile $teacher,
        ParentProfile $parent,
        ?StudentProfile $student,
        CarbonInterface $start,
        CarbonInterface $end,
        string $mode = 'online',
        ?string $ipAddress = null
    ): SlotHold {
        return DB::transaction(function () use ($teacher, $parent, $student, $start, $end, $mode, $ipAddress) {
            $this->lockTeacher($teacher->id);

            $check = $this->availability->isIntervalBookable($teacher, $start, $end, $mode);

            if (! $check['ok']) {
                throw new SlotUnavailableException($check['reason']);
            }

            $timeoutMinutes = (int) Platform::get('hold_timeout_minutes', 15);

            $hold = SlotHold::create([
                'token' => (string) Str::uuid(),
                'teacher_profile_id' => $teacher->id,
                'parent_profile_id' => $parent->id,
                'student_profile_id' => $student?->id,
                'mode' => $mode,
                'start_at' => $start,
                'end_at' => $end,
                'status' => SlotHold::STATUS_ACTIVE,
                'expires_at' => now()->addMinutes($timeoutMinutes),
                'ip_address' => $ipAddress,
            ]);

            $this->insertReservations($teacher->id, $start, $end, $hold->id, $hold->expires_at);

            return $hold->fresh();
        }, 3);
    }

    /**
     * Insert the reservation rows for every slot start in the interval.
     */
    public function insertReservations(
        int $teacherId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $holdId = null,
        ?CarbonInterface $expiresAt = null,
        ?int $bookingId = null,
        string $type = SlotReservation::TYPE_HOLD
    ): void {
        $starts = $this->availability->slotStartsFor($start, $end);
        $granularity = $this->availability->granularityMinutes();

        foreach ($starts as $slotStart) {
            try {
                SlotReservation::create([
                    'teacher_profile_id' => $teacherId,
                    'slot_start_at' => $slotStart,
                    'slot_end_at' => $slotStart->copy()->addMinutes($granularity),
                    'reservation_type' => $type,
                    'slot_hold_id' => $holdId,
                    'booking_id' => $bookingId,
                    'expires_at' => $expiresAt,
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    throw new SlotUnavailableException(
                        'This time slot was just taken by another parent. Please pick a different slot.'
                    );
                }

                throw $exception;
            }
        }
    }

    /**
     * Turn an active hold into a confirmed booking reservation.
     */
    public function convertHold(SlotHold $hold, Booking $booking): void
    {
        DB::transaction(function () use ($hold, $booking) {
            $this->lockTeacher($hold->teacher_profile_id);

            SlotReservation::where('slot_hold_id', $hold->id)->update([
                'reservation_type' => SlotReservation::TYPE_BOOKING,
                'booking_id' => $booking->id,
                'expires_at' => null,
            ]);

            $hold->update([
                'status' => SlotHold::STATUS_CONVERTED,
                'booking_id' => $booking->id,
            ]);
        });
    }

    /**
     * Release a hold (payment abandoned, timeout, or user navigated away).
     */
    public function releaseHold(SlotHold $hold): void
    {
        DB::transaction(function () use ($hold) {
            SlotReservation::where('slot_hold_id', $hold->id)->delete();

            if ($hold->status === SlotHold::STATUS_ACTIVE) {
                $hold->update(['status' => SlotHold::STATUS_RELEASED]);
            }
        });
    }

    /**
     * Free the slot(s) of a cancelled booking.
     */
    public function releaseForBooking(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $this->lockTeacher($booking->teacher_profile_id);

            SlotReservation::where('booking_id', $booking->id)->delete();
        });
    }

    /**
     * Release expired holds and their reservations.
     *
     * @return int number of holds expired
     */
    public function expireStaleHolds(): int
    {
        $holds = SlotHold::query()
            ->where('status', SlotHold::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->limit(200)
            ->get();

        foreach ($holds as $hold) {
            $this->releaseHold($hold);
            $hold->update(['status' => SlotHold::STATUS_EXPIRED]);
        }

        return $holds->count();
    }

    /**
     * Find an active hold for the given parent that still covers the interval.
     */
    public function findActiveHold(
        int $teacherId,
        int $parentId,
        CarbonInterface $start,
        CarbonInterface $end
    ): ?SlotHold {
        return SlotHold::query()
            ->where('teacher_profile_id', $teacherId)
            ->where('parent_profile_id', $parentId)
            ->where('status', SlotHold::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->where('start_at', $start)
            ->where('end_at', $end)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Lock the teacher row so concurrent booking attempts are serialised.
     */
    public function lockTeacher(int $teacherId): void
    {
        DB::table('teacher_profiles')->where('id', $teacherId)->lockForUpdate()->first();
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || $exception->getCode() === '23000';
    }
}
