<?php

namespace App\Services;

use App\Models\SlotReservation;
use App\Models\TeacherAvailabilityRule;
use App\Models\TeacherProfile;
use App\Models\TeacherTimeSlot;
use App\Support\Platform;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds the teacher availability calendar.
 *
 * Slot colours (always rendered with a text label as well):
 *  - green  : available
 *  - gray   : already booked / unavailable
 *  - amber  : temporarily held while a customer completes checkout
 *  - blue   : selected by the current customer
 *  - red    : cancelled or failed (visible to authorised users only)
 */
class AvailabilityService
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_HELD = 'held';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_SELECTED = 'selected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_AVAILABLE => 'Available',
        self::STATUS_BOOKED => 'Booked',
        self::STATUS_HELD => 'On hold',
        self::STATUS_BLOCKED => 'Unavailable',
        self::STATUS_SELECTED => 'Selected',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public function granularityMinutes(): int
    {
        return max(5, (int) Platform::get('slot_granularity_minutes', 30));
    }

    /**
     * Full slot list for one teacher on one date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function slotsForDate(TeacherProfile $teacher, CarbonInterface $date, ?string $mode = null): Collection
    {
        $date = CarbonImmutable::parse($date->toDateString());
        $granularity = $this->granularityMinutes();

        $rules = $this->rulesForDate($teacher, $date);
        $overrides = TeacherTimeSlot::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('slot_date', $date->toDateString())
            ->where('is_active', true)
            ->get();

        /** @var array<string, array<string, mixed>> $grid */
        $grid = [];

        foreach ($rules as $rule) {
            $start = CarbonImmutable::parse($date->toDateString().' '.$rule->start_time);
            $end = CarbonImmutable::parse($date->toDateString().' '.$rule->end_time);

            while ($start->lt($end)) {
                $slotEnd = $start->addMinutes($granularity);

                if ($slotEnd->gt($end)) {
                    break;
                }

                $key = $start->format('H:i');
                $grid[$key] = [
                    'start_at' => $start->toDateTimeString(),
                    'end_at' => $slotEnd->toDateTimeString(),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $granularity,
                    'mode' => $rule->mode,
                    'source' => 'weekly_rule',
                    'status' => self::STATUS_AVAILABLE,
                    'note' => null,
                ];

                $start = $slotEnd;
            }
        }

        // One-off slots explicitly added by the teacher.
        foreach ($overrides->where('status', TeacherTimeSlot::STATUS_AVAILABLE) as $slot) {
            $start = CarbonImmutable::parse($date->toDateString().' '.$slot->start_time);
            $end = CarbonImmutable::parse($date->toDateString().' '.$slot->end_time);
            $key = $start->format('H:i');

            if (! isset($grid[$key])) {
                $grid[$key] = [
                    'start_at' => $start->toDateTimeString(),
                    'end_at' => $end->toDateTimeString(),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'duration_minutes' => (int) $start->diffInMinutes($end),
                    'mode' => $slot->mode,
                    'source' => 'one_off',
                    'status' => self::STATUS_AVAILABLE,
                    'note' => $slot->note,
                ];
            }
        }

        // Blocked periods and holidays remove availability.
        foreach ($overrides->whereIn('status', [TeacherTimeSlot::STATUS_BLOCKED, TeacherTimeSlot::STATUS_HOLIDAY]) as $slot) {
            $blockStart = CarbonImmutable::parse($date->toDateString().' '.$slot->start_time);
            $blockEnd = CarbonImmutable::parse($date->toDateString().' '.$slot->end_time);

            foreach ($grid as $key => $item) {
                $itemStart = CarbonImmutable::parse($item['start_at']);
                $itemEnd = CarbonImmutable::parse($item['end_at']);

                if ($itemStart->lt($blockEnd) && $itemEnd->gt($blockStart)) {
                    $grid[$key]['status'] = self::STATUS_BLOCKED;
                    $grid[$key]['note'] = $slot->note ?: ($slot->status === TeacherTimeSlot::STATUS_HOLIDAY ? 'Holiday' : 'Blocked');
                }
            }
        }

        $grid = collect($grid)->sortBy('start_time')->values();

        $this->applyReservationState($teacher, $grid, $date);

        if ($mode) {
            $grid = $grid->filter(function (array $slot) use ($mode) {
                return in_array($slot['mode'], ['both', $mode], true);
            })->values();
        }

        return $grid;
    }

    /**
     * Overlay booking / hold state onto the grid.
     */
    public function applyReservationState(TeacherProfile $teacher, Collection $grid, CarbonInterface $date): void
    {
        $reservations = SlotReservation::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where(function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->where('reservation_type', SlotReservation::TYPE_BOOKING)
                        ->whereBetween('slot_start_at', [$date->startOfDay(), $date->endOfDay()]);
                })->orWhere(function ($q) use ($date) {
                    $q->where('reservation_type', SlotReservation::TYPE_HOLD)
                        ->where('expires_at', '>', now())
                        ->whereBetween('slot_start_at', [$date->startOfDay(), $date->endOfDay()]);
                });
            })
            ->get();

        foreach ($grid as $index => $slot) {
            $slotStart = CarbonImmutable::parse($slot['start_at']);
            $slotEnd = CarbonImmutable::parse($slot['end_at']);

            foreach ($reservations as $reservation) {
                $resStart = CarbonImmutable::parse($reservation->slot_start_at);
                $resEnd = CarbonImmutable::parse($reservation->slot_end_at);

                if ($slotStart->lt($resEnd) && $slotEnd->gt($resStart)) {
                    $grid[$index]['status'] = $reservation->reservation_type === SlotReservation::TYPE_BOOKING
                        ? self::STATUS_BOOKED
                        : self::STATUS_HELD;

                    $grid[$index]['booking_id'] = $reservation->booking_id;
                    $grid[$index]['label'] = self::STATUS_LABELS[$grid[$index]['status']];
                    break;
                }
            }
        }
    }

    /**
     * Weekly rules that apply to a given calendar date.
     */
    public function rulesForDate(TeacherProfile $teacher, CarbonInterface $date): Collection
    {
        return TeacherAvailabilityRule::query()
            ->where('teacher_profile_id', $teacher->id)
            ->where('is_active', true)
            ->where('weekday', $date->dayOfWeek)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date->toDateString());
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Calendar summary: number of free slots per date for a month.
     *
     * @return array<string, array<string, mixed>>
     */
    public function monthSummary(TeacherProfile $teacher, int $year, int $month, ?string $mode = null): array
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();
        $summary = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $slots = $this->slotsForDate($teacher, $date, $mode);
            $available = $slots->where('status', self::STATUS_AVAILABLE)->count();

            $summary[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'available_slots' => $available,
                'total_slots' => $slots->count(),
                'is_available' => $available > 0,
            ];
        }

        return $summary;
    }

    /**
     * Check whether a concrete interval can be booked by this teacher.
     *
     * @return array{ok: bool, reason: ?string}
     */
    public function isIntervalBookable(
        TeacherProfile $teacher,
        CarbonInterface $start,
        CarbonInterface $end,
        ?string $mode = null,
        ?int $ignoreBookingId = null
    ): array {
        if ($end->lte($start)) {
            return ['ok' => false, 'reason' => 'The end time must be after the start time.'];
        }

        $minNotice = (int) Platform::get('min_booking_notice_hours', 2);
        if ($start->lt(Carbon::now()->addHours($minNotice))) {
            return ['ok' => false, 'reason' => "Bookings require at least {$minNotice} hour(s) of notice."];
        }

        $granularity = $this->granularityMinutes();
        if ($start->minute % $granularity !== 0 || $start->second !== 0) {
            return ['ok' => false, 'reason' => 'The selected time does not align with the teacher\'s slot grid.'];
        }

        $duration = $start->diffInMinutes($end);
        if ($duration % $granularity !== 0) {
            return ['ok' => false, 'reason' => 'The session duration must be a multiple of the slot interval.'];
        }

        $maxMinutes = (int) Platform::get('max_session_minutes', 240);
        if ($duration > $maxMinutes) {
            return ['ok' => false, 'reason' => "A single session cannot exceed {$maxMinutes} minutes."];
        }

        $slots = $this->slotsForDate($teacher, $start, $mode);

        if ($slots->isEmpty()) {
            return ['ok' => false, 'reason' => 'The teacher is not available on this date.'];
        }

        $needed = [];
        $cursor = $start;
        while ($cursor->lt($end)) {
            $needed[] = $cursor->format('H:i');
            $cursor = $cursor->addMinutes($granularity);
        }

        foreach ($needed as $time) {
            $slot = $slots->firstWhere('start_time', $time);

            if (! $slot) {
                return ['ok' => false, 'reason' => 'The teacher is not available for the whole requested duration.'];
            }

            if ($slot['status'] === self::STATUS_BLOCKED) {
                return ['ok' => false, 'reason' => 'The teacher has blocked this time.'];
            }

            if ($slot['status'] === self::STATUS_BOOKED) {
                return ['ok' => false, 'reason' => 'This slot has already been booked by another parent.'];
            }

            if ($slot['status'] === self::STATUS_HELD) {
                if ($ignoreBookingId && ($slot['booking_id'] ?? null) === $ignoreBookingId) {
                    continue;
                }

                return ['ok' => false, 'reason' => 'This slot is temporarily held by another customer. Please try again shortly.'];
            }
        }

        return ['ok' => true, 'reason' => null];
    }

    /**
     * The list of concrete slot starts a booking should reserve.
     *
     * @return array<int, CarbonInterface>
     */
    public function slotStartsFor(CarbonInterface $start, CarbonInterface $end): array
    {
        $granularity = $this->granularityMinutes();
        $starts = [];
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $starts[] = $cursor->copy();
            $cursor = $cursor->addMinutes($granularity);
        }

        return $starts;
    }
}
