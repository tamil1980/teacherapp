<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Review;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\TeacherSearchService;
use App\Support\Platform;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDirectoryController extends Controller
{
    public function __construct(
        private readonly TeacherSearchService $search,
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $teachers = $this->search->search($filters, 12);
        $options = $this->search->filterOptions();
        $priceRange = $this->search->priceRange($filters);

        return view('teachers.index', compact('teachers', 'options', 'filters', 'priceRange'));
    }

    public function show(Request $request, TeacherProfile $teacher): View
    {
        abort_unless($teacher->isApproved(), 404);

        $teacher->load([
            'user',
            'teacherSubjects.subject',
            'teacherBoards.board',
            'teacherClassStandards.classStandard',
            'teachingModes',
            'locations',
            'qualifications',
        ]);

        $reviews = Review::where('teacher_profile_id', $teacher->id)
            ->where('status', 'published')
            ->with(['parent.user'])
            ->latest()
            ->paginate(10);

        $ratingBreakdown = Review::where('teacher_profile_id', $teacher->id)
            ->where('status', 'published')
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $fees = $teacher->fees()
            ->with(['subject', 'classStandard', 'board'])
            ->where('is_active', true)
            ->get();

        $lowestRate = $this->search->lowestRate($teacher);

        $packages = $this->pricing->packagesFor($teacher);

        $availability = $this->availability->slotsForDate($teacher, now()->addDay());

        return view('teachers.show', compact(
            'teacher',
            'reviews',
            'ratingBreakdown',
            'fees',
            'lowestRate',
            'packages',
            'availability'
        ));
    }

    /**
     * Normalise the request into a filter array.
     *
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return array_filter([
            'q' => $request->string('q')->trim()->value() ?: null,
            'subject_id' => $request->integer('subject_id') ?: null,
            'subject' => $request->string('subject')->trim()->value() ?: null,
            'board_id' => $request->integer('board_id') ?: null,
            'board' => $request->string('board')->trim()->value() ?: null,
            'class_standard_id' => $request->integer('class_standard_id') ?: null,
            'class' => $request->string('class')->trim()->value() ?: null,
            'mode' => $request->string('mode')->trim()->value() ?: null,
            'city' => $request->string('city')->trim()->value() ?: null,
            'state' => $request->string('state')->trim()->value() ?: null,
            'pincode' => $request->string('pincode')->trim()->value() ?: null,
            'min_fee' => $request->input('min_fee') !== null && $request->input('min_fee') !== '' ? (float) $request->input('min_fee') : null,
            'max_fee' => $request->input('max_fee') !== null && $request->input('max_fee') !== '' ? (float) $request->input('max_fee') : null,
            'min_rating' => $request->input('min_rating') !== null && $request->input('min_rating') !== '' ? (float) $request->input('min_rating') : null,
            'experience' => $request->input('experience') !== null && $request->input('experience') !== '' ? (int) $request->input('experience') : null,
            'latitude' => $request->input('latitude') ?: null,
            'longitude' => $request->input('longitude') ?: null,
            'distance_km' => $request->input('distance_km') ?: null,
            'available_on' => $request->string('available_on')->trim()->value() ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
