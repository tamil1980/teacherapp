<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Services\TeacherSearchService;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly TeacherSearchService $search) {}

    public function subjects(): View
    {
        $subjects = Subject::where('is_active', true)
            ->withCount(['teacherSubjects' => fn ($q) => $q->whereHas('teacher', fn ($t) => $t->where('verification_status', 'approved'))])
            ->orderBy('sort_order')
            ->get();

        return view('catalog.subjects', compact('subjects'));
    }

    public function subject(Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $teachers = $this->search->search(['subject_id' => $subject->id], 12);
        $boards = Board::where('is_active', true)->orderBy('sort_order')->get();
        $classes = ClassStandard::where('is_active', true)->orderBy('sort_order')->get();

        return view('catalog.subject', compact('subject', 'teachers', 'boards', 'classes'));
    }

    public function boards(): View
    {
        $boards = Board::where('is_active', true)->orderBy('sort_order')->get();

        return view('catalog.boards', compact('boards'));
    }

    public function board(Board $board): View
    {
        abort_unless($board->is_active, 404);

        $teachers = $this->search->search(['board_id' => $board->id], 12);
        $subjects = $this->search->subjectsForBoard($board);
        $classes = ClassStandard::where('is_active', true)->orderBy('sort_order')->get();

        return view('catalog.board', compact('board', 'teachers', 'subjects', 'classes'));
    }

    public function classes(): View
    {
        $classes = ClassStandard::where('is_active', true)->orderBy('sort_order')->get();

        return view('catalog.classes', compact('classes'));
    }

    public function class(ClassStandard $classStandard): View
    {
        abort_unless($classStandard->is_active, 404);

        $teachers = $this->search->search(['class_standard_id' => $classStandard->id], 12);
        $boards = Board::where('is_active', true)->orderBy('sort_order')->get();
        $subjects = Subject::where('is_active', true)->orderBy('sort_order')->get();

        return view('catalog.class', compact('classStandard', 'teachers', 'boards', 'subjects'));
    }
}
