<?php

namespace App\Services;

use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Support\Platform;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Teacher discovery. Only approved, active teachers are returned to the
 * public — a pending, rejected or suspended teacher never appears in
 * search results.
 */
class TeacherSearchService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = TeacherProfile::query()
            ->with(['user', 'teacherSubjects.subject', 'teacherBoards.board', 'teacherClassStandards.classStandard', 'teachingModes', 'locations', 'fees'])
            ->where('verification_status', 'approved')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'));

        $this->applyFilters($query, $filters);

        $query->orderByDesc('rating_average')
            ->orderByDesc('completed_sessions_count');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['subject_id']) || ! empty($filters['subject'])) {
            $subjectId = $filters['subject_id'] ?? Subject::where('slug', $filters['subject'])->value('id');

            if ($subjectId) {
                $query->whereHas('teacherSubjects', fn ($q) => $q->where('subject_id', $subjectId));
            }
        }

        if (! empty($filters['board_id']) || ! empty($filters['board'])) {
            $boardId = $filters['board_id'] ?? Board::where('slug', $filters['board'])->value('id');

            if ($boardId) {
                $query->whereHas('teacherBoards', fn ($q) => $q->where('board_id', $boardId));
            }
        }

        if (! empty($filters['class_standard_id']) || ! empty($filters['class'])) {
            $classId = $filters['class_standard_id'] ?? ClassStandard::where('code', $filters['class'])->value('id');

            if ($classId) {
                $query->whereHas('teacherClassStandards', fn ($q) => $q->where('class_standard_id', $classId));
            }
        }

        if (! empty($filters['mode']) && in_array($filters['mode'], ['online', 'offline'], true)) {
            $query->whereHas('teachingModes', fn ($q) => $q->where('mode', $filters['mode']));
        }

        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%'.$filters['city'].'%');
        }

        if (! empty($filters['state'])) {
            $query->where('state', 'like', '%'.$filters['state'].'%');
        }

        if (! empty($filters['pincode'])) {
            $query->where('pincode', $filters['pincode']);
        }

        if (! empty($filters['min_fee']) || ! empty($filters['max_fee'])) {
            $min = (float) ($filters['min_fee'] ?? 0);
            $max = (float) ($filters['max_fee'] ?? PHP_INT_MAX);

            $query->whereHas('fees', function ($q) use ($min, $max) {
                $q->where('is_active', true)->whereBetween('hourly_rate', [$min, $max]);
            });
        }

        if (! empty($filters['min_rating'])) {
            $query->where('rating_average', '>=', (float) $filters['min_rating']);
        }

        if (! empty($filters['experience'])) {
            $query->where('experience_years', '>=', (int) $filters['experience']);
        }

        if (! empty($filters['verified_only'])) {
            $query->whereNotNull('verified_at');
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';

            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term))
                    ->orWhere('headline', 'like', $term)
                    ->orWhere('qualification_summary', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        // Distance filtering (only possible when coordinates are supplied).
        if (! empty($filters['latitude']) && ! empty($filters['longitude']) && ! empty($filters['distance_km'])) {
            $lat = (float) $filters['latitude'];
            $lng = (float) $filters['longitude'];
            $radius = (float) $filters['distance_km'];

            $query->whereHas('locations', function ($q) use ($lat, $lng, $radius) {
                $q->whereNotNull('latitude')->whereNotNull('longitude')
                    ->whereRaw(
                        '(6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude))))) <= ?',
                        [$lat, $lng, $lat, $radius]
                    );
            });
        }

        return $query;
    }

    /**
     * Price range of the matching teachers — used to render the filter slider.
     *
     * @param  array<string, mixed>  $filters
     * @return array{min: float, max: float}
     */
    public function priceRange(array $filters = []): array
    {
        $query = TeacherProfile::query()
            ->where('verification_status', 'approved')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'));

        $this->applyFilters($query, $filters);

        $rates = (clone $query)->join('teacher_fees', 'teacher_fees.teacher_profile_id', '=', 'teacher_profiles.id')
            ->where('teacher_fees.is_active', true)
            ->min('teacher_fees.hourly_rate');

        $max = (clone $query)->join('teacher_fees', 'teacher_fees.teacher_profile_id', '=', 'teacher_profiles.id')
            ->where('teacher_fees.is_active', true)
            ->max('teacher_fees.hourly_rate');

        return [
            'min' => (float) ($rates ?? 0),
            'max' => (float) ($max ?? 0),
        ];
    }

    /**
     * The lowest rate offered by a teacher for the given filters.
     */
    public function lowestRate(TeacherProfile $teacher, array $filters = []): ?float
    {
        $query = $teacher->fees()->where('is_active', true);

        if (! empty($filters['subject_id'])) {
            $query->where(fn ($q) => $q->whereNull('subject_id')->orWhere('subject_id', $filters['subject_id']));
        }

        if (! empty($filters['mode'])) {
            $query->where(fn ($q) => $q->where('mode', $filters['mode'])->orWhere('mode', 'both'));
        }

        $rate = (clone $query)->min('hourly_rate');

        return $rate === null ? null : (float) $rate;
    }

    /**
     * Build the filter option lists used by the search form.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'boards' => Board::where('is_active', true)->orderBy('sort_order')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(),
            'classes' => ClassStandard::where('is_active', true)->orderBy('sort_order')->get(),
            'modes' => [
                ['value' => 'online', 'label' => 'Online classes'],
                ['value' => 'offline', 'label' => 'Offline / home tuition'],
            ],
            'slot_granularity' => (int) Platform::get('slot_granularity_minutes', 30),
        ];
    }

    /**
     * Filter options restricted to a specific board or subject.
     *
     * @return Collection<int, Subject>
     */
    public function subjectsForBoard(?Board $board): Collection
    {
        if (! $board) {
            return Subject::where('is_active', true)->orderBy('sort_order')->get();
        }

        return Subject::where('is_active', true)
            ->whereHas('teacherSubjects', fn ($q) => $q->whereHas('teacher', fn ($t) => $t->whereHas('teacherBoards', fn ($b) => $b->where('board_id', $board->id))))
            ->orderBy('sort_order')
            ->get();
    }
}
