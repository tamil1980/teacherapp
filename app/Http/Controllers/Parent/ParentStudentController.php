<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentStudentController extends Controller
{
    public function index(): View
    {
        $students = $this->parentProfile()
            ->students()
            ->with(['classStandard', 'board'])
            ->latest()
            ->get();

        return view('parent.students.index', compact('students'));
    }

    public function create(): View
    {
        return view('parent.students.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $student = $this->parentProfile()->students()->create([
            'display_name' => $validated['display_name'],
            'class_standard_id' => $validated['class_standard_id'] ?? null,
            'board_id' => $validated['board_id'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'school_name' => $validated['school_name'] ?? null,
            'learning_requirements' => $validated['learning_requirements'] ?? null,
            'subject_interests' => $validated['subject_interests'] ?? [],
            'is_active' => true,
        ]);

        return redirect()->route('parent.students.index')
            ->with('success', 'Child profile "'.$student->display_name.'" created.');
    }

    public function show(StudentProfile $student): View
    {
        $this->authorizeStudent($student);

        $student->load(['classStandard', 'board']);

        $bookings = $student->bookings()
            ->with(['teacher.user', 'subject'])
            ->latest()
            ->paginate(10);

        return view('parent.students.show', compact('student', 'bookings'));
    }

    public function edit(StudentProfile $student): View
    {
        $this->authorizeStudent($student);

        return view('parent.students.edit', array_merge($this->formOptions(), ['student' => $student]));
    }

    public function update(Request $request, StudentProfile $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $validated = $request->validate($this->rules());

        $student->update([
            'display_name' => $validated['display_name'],
            'class_standard_id' => $validated['class_standard_id'] ?? null,
            'board_id' => $validated['board_id'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'school_name' => $validated['school_name'] ?? null,
            'learning_requirements' => $validated['learning_requirements'] ?? null,
            'subject_interests' => $validated['subject_interests'] ?? [],
        ]);

        return redirect()->route('parent.students.show', $student)
            ->with('success', 'Child profile updated.');
    }

    public function destroy(StudentProfile $student): RedirectResponse
    {
        $this->authorizeStudent($student);

        $student->delete();

        return redirect()->route('parent.students.index')
            ->with('success', 'Child profile removed.');
    }

    /**
     * A parent may only access their own children.
     */
    private function authorizeStudent(StudentProfile $student): void
    {
        abort_unless(
            $this->parentProfile()?->id === $student->parent_profile_id,
            403,
            'You are not allowed to access this child profile.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'school_name' => ['nullable', 'string', 'max:180'],
            'learning_requirements' => ['nullable', 'string', 'max:2000'],
            'subject_interests' => ['nullable', 'array'],
            'subject_interests.*' => ['integer', 'exists:subjects,id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'boards' => Board::where('is_active', true)->orderBy('sort_order')->get(),
            'classes' => ClassStandard::where('is_active', true)->orderBy('sort_order')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->get(),
        ];
    }
}
