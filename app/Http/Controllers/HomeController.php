<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Review;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Services\AvailabilityService;
use App\Services\TeacherSearchService;
use App\Support\Platform;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly TeacherSearchService $search,
        private readonly AvailabilityService $availability,
    ) {}

    public function index(): View
    {
        $featured = $this->search->search([], 8);

        $subjects = Subject::where('is_active', true)->orderBy('sort_order')->take(12)->get();
        $boards = Board::where('is_active', true)->orderBy('sort_order')->get();
        $classes = ClassStandard::where('is_active', true)->orderBy('sort_order')->get();

        $stats = [
            'teachers' => TeacherProfile::where('verification_status', 'approved')->count(),
            'subjects' => Subject::where('is_active', true)->count(),
            'boards' => Board::where('is_active', true)->count(),
            'classes' => ClassStandard::where('is_active', true)->count(),
            'reviews' => Review::where('status', 'published')->count(),
        ];

        return view('home', compact('featured', 'subjects', 'boards', 'classes', 'stats'));
    }
}
