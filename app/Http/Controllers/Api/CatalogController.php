<?php

namespace App\Http\Controllers\Api;

use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public catalogue: subjects, boards, classes, the teacher directory and
 * a teacher's bookable slots.
 */
class CatalogController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->ok([
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(),
            'boards' => Board::where('is_active', true)->orderBy('sort_order')->get(),
            'classes' => ClassStandard::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function teachers(Request $request): JsonResponse
    {
        $query = TeacherProfile::query()
            ->where('verification_status', 'approved')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->with([
                'user:id,name',
                'teacherSubjects.subject:id,name,code',
                'teacherBoards.board:id,name,code',
                'teacherClassStandards.classStandard:id,name,code',
                'teachingModes',
                'locations',
                'fees' => fn ($q) => $q->where('is_active', true),
            ]);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('headline', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('qualification_summary', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('teacherSubjects.subject', fn ($s) => $s->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($subject = $request->query('subject_id')) {
            $query->whereHas('teacherSubjects', fn ($q) => $q->where('subject_id', $subject));
        }

        if ($board = $request->query('board_id')) {
            $query->whereHas('teacherBoards', fn ($q) => $q->where('board_id', $board));
        }

        if ($class = $request->query('class_standard_id')) {
            $query->whereHas('teacherClassStandards', fn ($q) => $q->where('class_standard_id', $class));
        }

        if ($mode = $request->query('mode')) {
            $query->whereHas('teachingModes', fn ($q) => $q->where('mode', $mode));
        }

        if ($city = $request->query('city')) {
            $query->where('city', 'like', '%'.$city.'%');
        }

        $paginator = $query->orderByDesc('rating_average')
            ->orderByDesc('completed_sessions_count')
            ->paginate($this->perPage());

        return $this->paginated($paginator, fn (TeacherProfile $teacher) => $this->summary($teacher));
    }

    public function teacher(TeacherProfile $teacher): JsonResponse
    {
        abort_unless($teacher->verification_status === 'approved', 404, 'Teacher not found.');

        $teacher->load([
            'user:id,name,email,mobile,avatar_path',
            'teacherSubjects.subject',
            'teacherBoards.board',
            'teacherClassStandards.classStandard',
            'teachingModes',
            'locations',
            'fees' => fn ($q) => $q->where('is_active', true)->with(['subject', 'classStandard', 'board']),
            'qualifications',
            'reviews' => fn ($q) => $q->where('status', 'approved')->latest()->limit(20),
        ]);

        $packages = $teacher->hasMany(\App\Models\ClassPackage::class, 'teacher_profile_id')
            ->where('is_active', true)
            ->get();

        return $this->ok([
            'teacher' => array_merge($this->summary($teacher), [
                'bio' => $teacher->bio,
                'teaching_description' => $teacher->teaching_description,
                'methodology' => $teacher->methodology,
                'qualification_summary' => $teacher->qualification_summary,
                'languages' => $teacher->languages ?? [],
                'experience_years' => (int) $teacher->experience_years,
                'default_session_minutes' => (int) $teacher->default_session_minutes,
                'cancellation_policy' => $teacher->cancellation_policy,
                'service_radius_km' => (float) $teacher->service_radius_km,
                'accepts_online' => (bool) $teacher->accepts_online,
                'accepts_offline' => (bool) $teacher->accepts_offline,
                'user' => $teacher->user ? [
                    'id' => $teacher->user->id,
                    'name' => $teacher->user->name,
                ] : null,
                'fees' => $teacher->fees,
                'packages' => $packages,
                'qualifications' => $teacher->qualifications,
                'reviews' => $teacher->reviews,
            ]),
        ]);
    }

    /**
     * Bookable slots for one day (or a whole week when `date` is omitted).
     */
    public function slots(Request $request, TeacherProfile $teacher, AvailabilityService $availability): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'mode' => ['nullable', 'in:online,offline'],
        ]);

        $date = Carbon::parse($validated['date'] ?? now()->toDateString());
        $mode = $validated['mode'] ?? null;

        $slots = $availability->slotsForDate($teacher, $date, $mode)
            ->filter(fn (array $slot) => $slot['status'] === AvailabilityService::STATUS_AVAILABLE)
            ->values();

        return $this->ok([
            'date' => $date->toDateString(),
            'granularity_minutes' => $availability->granularityMinutes(),
            'slots' => $slots,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(TeacherProfile $teacher): array
    {
        $fees = $teacher->relationLoaded('fees') ? $teacher->fees : $teacher->fees()->where('is_active', true)->get();

        return [
            'id' => $teacher->id,
            'name' => $teacher->user?->name,
            'slug' => $teacher->slug,
            'headline' => $teacher->headline,
            'city' => $teacher->city,
            'state' => $teacher->state,
            'photo_path' => $teacher->photo_path,
            'rating_average' => (float) $teacher->rating_average,
            'rating_count' => (int) $teacher->rating_count,
            'completed_sessions_count' => (int) $teacher->completed_sessions_count,
            'experience_years' => (int) $teacher->experience_years,
            'hourly_rate_from' => $fees->isEmpty() ? null : (float) $fees->min('hourly_rate'),
            'subjects' => $teacher->teacherSubjects->map(fn ($row) => $row->subject)->filter()->pluck('name')->values(),
            'boards' => $teacher->teacherBoards->map(fn ($row) => $row->board)->filter()->pluck('name')->values(),
            'classes' => $teacher->teacherClassStandards->map(fn ($row) => $row->classStandard)->filter()->pluck('name')->values(),
            'modes' => $teacher->teachingModes->pluck('mode')->values(),
            'locations' => $teacher->locations,
        ];
    }
}
