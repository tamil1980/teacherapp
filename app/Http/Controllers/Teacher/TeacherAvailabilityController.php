<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAvailabilityRule;
use App\Models\TeacherProfile;
use App\Models\TeacherTimeSlot;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Weekly availability rules, one-off slots and the month calendar.
 */
class TeacherAvailabilityController extends Controller
{
    public const MODES = [
        'both' => 'Online & offline',
        'online' => 'Online only',
        'offline' => 'Offline only',
    ];

    public const SLOT_STATUSES = [
        'available' => 'Available',
        'blocked' => 'Blocked',
        'holiday' => 'Holiday',
    ];

    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Availability overview.
     */
    public function index(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $rules = $teacher->availabilityRules()
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $slots = $teacher->timeSlots()
            ->where('slot_date', '>=', now()->toDateString())
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->limit(60)
            ->get();

        [$year, $monthNumber] = $this->requestedMonth($request);

        $calendar = $this->calendarData($teacher, $year, $monthNumber);

        return view('teacher.availability.index', [
            'teacher' => $teacher,
            'rules' => $rules,
            'slots' => $slots,
            'calendar' => $calendar,
            'modes' => self::MODES,
            'slotStatuses' => self::SLOT_STATUSES,
            'weekdays' => TeacherAvailabilityRule::WEEKDAYS,
            'granularity' => $this->availability->granularityMinutes(),
        ]);
    }

    /**
     * Add a weekly availability rule.
     */
    public function storeRule(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $this->validateRule($request);

        $teacher->availabilityRules()->create($this->ruleAttributes($validated));

        return back()->with('success', 'The weekly availability rule has been added.');
    }

    public function updateRule(Request $request, TeacherAvailabilityRule $rule)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $rule->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only edit your own availability rules.');
        }

        $validated = $this->validateRule($request);
        $rule->update($this->ruleAttributes($validated));

        return back()->with('success', 'The weekly availability rule has been updated.');
    }

    /**
     * Remove a weekly availability rule.
     */
    public function destroyRule(TeacherAvailabilityRule $rule)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $rule->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own availability rules.');
        }

        $rule->delete();

        return back()->with('success', 'The availability rule has been removed.');
    }

    /**
     * Add a one-off slot (extra availability, a block or a holiday).
     */
    public function storeSlot(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $this->validateSlot($request);

        try {
            $teacher->timeSlots()->create($this->slotAttributes($validated));
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                return back()->withInput()
                    ->withErrors(['start_time' => 'A slot starting at this time already exists on that date.']);
            }

            throw $exception;
        }

        return back()->with('success', 'The one-off slot has been added.');
    }

    public function updateSlot(Request $request, TeacherTimeSlot $slot)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $slot->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only edit your own one-off slots.');
        }

        $validated = $this->validateSlot($request);

        try {
            $slot->update($this->slotAttributes($validated));
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                return back()->withInput()
                    ->withErrors(['start_time' => 'A slot starting at this time already exists on that date.']);
            }

            throw $exception;
        }

        return back()->with('success', 'The one-off slot has been updated.');
    }

    /**
     * Remove a one-off slot.
     */
    public function destroySlot(TeacherTimeSlot $slot)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $slot->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own one-off slots.');
        }

        $slot->delete();

        return back()->with('success', 'The one-off slot has been removed.');
    }

    /**
     * JSON month summary used by the calendar widget.
     */
    public function calendar(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        [$year, $monthNumber] = $this->requestedMonth($request);

        return response()->json($this->calendarData($teacher, $year, $monthNumber));
    }

    /**
     * Build the month grid: availability plus per-status counters.
     *
     * @return array<string, mixed>
     */
    protected function calendarData(TeacherProfile $teacher, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // The authoritative month grid (available / total slots per day).
        $summary = $this->availability->monthSummary($teacher, $year, $month);

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $row = $summary[$key] ?? [
                'date' => $key,
                'available_slots' => 0,
                'total_slots' => 0,
                'is_available' => false,
            ];

            $counts = [
                'available' => (int) $row['available_slots'],
                'booked' => 0,
                'held' => 0,
                'blocked' => 0,
                'cancelled' => 0,
                'selected' => 0,
            ];

            if ((int) $row['total_slots'] > 0) {
                foreach ($this->availability->slotsForDate($teacher, $date) as $slot) {
                    $status = (string) $slot['status'];

                    if (array_key_exists($status, $counts)) {
                        $counts[$status]++;
                    }
                }
            }

            $days[$key] = [
                'date' => $key,
                'day' => (int) $date->day,
                'weekday' => $date->dayOfWeek,
                'is_today' => $date->isSameDay(now()),
                'is_past' => $date->endOfDay()->isPast(),
                'total_slots' => (int) $row['total_slots'],
                'available_slots' => (int) $row['available_slots'],
                'is_available' => (bool) $row['is_available'],
                'counts' => $counts,
            ];
        }

        $previous = $start->copy()->subMonth();
        $next = $start->copy()->addMonth();

        return [
            'teacher_id' => $teacher->id,
            'month' => $start->format('Y-m'),
            'label' => $start->format('F Y'),
            'previous_month' => $previous->format('Y-m'),
            'next_month' => $next->format('Y-m'),
            'granularity_minutes' => $this->availability->granularityMinutes(),
            'labels' => AvailabilityService::STATUS_LABELS,
            'days' => $days,
        ];
    }

    /**
     * Resolve the requested month, defaulting to the current one.
     *
     * @return array{0: int, 1: int}
     */
    protected function requestedMonth(Request $request): array
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        try {
            $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            $date = now()->startOfMonth();
        }

        return [(int) $date->year, (int) $date->month];
    }

    protected function isUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || $exception->getCode() === '23000';
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRule(Request $request): array
    {
        return $request->validate([
            'weekday' => ['required', 'integer', 'between:0,6'],
            'start_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $weekday = $request->input('weekday');
                    $endTime = $request->input('end_time');

                    if (! is_numeric($weekday) || ! is_string($endTime) || ! $endTime) {
                        return;
                    }

                    $today = now()->copy()->startOfDay();
                    $daysUntil = (((int) $weekday - $today->dayOfWeek) + 7) % 7;
                    $date = $today->copy()->addDays($daysUntil);
                    $start = Carbon::parse($date->toDateString().' '.$value);
                    $end = Carbon::parse($date->toDateString().' '.$endTime);

                    if ($end->lte(now())) {
                        $fail('That weekly window has already passed. Please pick a later time or another weekday.');
                    }
                },
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $start = $request->input('start_time');

                    if (is_string($start) && $start !== '' && $value <= $start) {
                        $fail('The end time must be after the start time.');
                    }
                },
            ],
            'mode' => ['required', 'string', Rule::in(array_keys(self::MODES))],
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
    private function ruleAttributes(array $validated): array
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
    private function validateSlot(Request $request): array
    {
        return $request->validate([
            'slot_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $end = $request->input('end_time');

                    if (is_string($end) && $end !== '' && $value >= $end) {
                        $fail('The end time must be after the start time.');
                    }
                },
            ],
            'end_time' => ['required', 'date_format:H:i'],
            'status' => ['required', 'string', Rule::in(array_keys(self::SLOT_STATUSES))],
            'mode' => ['required', 'string', Rule::in(array_keys(self::MODES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function slotAttributes(array $validated): array
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
}
