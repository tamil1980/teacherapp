<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardVariant;
use App\Models\Booking;
use App\Models\ClassPackage;
use App\Models\ClassStandard;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherBoard;
use App\Models\TeacherClassStandard;
use App\Models\TeacherFee;
use App\Models\TeacherSubject;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The catalog: boards, subjects and class standards.
 */
class AdminCatalogController extends Controller
{
    use AuditsActions;

    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'boards');

        if (! in_array($tab, ['boards', 'subjects', 'classes'], true)) {
            $tab = 'boards';
        }

        return view('admin.catalog.index', [
            'tab' => $tab,
            'boards' => Board::query()->orderBy('sort_order')->orderBy('name')->get(),
            'subjects' => Subject::query()->orderBy('sort_order')->orderBy('name')->get(),
            'classes' => ClassStandard::query()->orderBy('level_order')->orderBy('name')->get(),
            'usage' => [
                'board' => $this->usageMap('board_id', [
                    'teacher board list' => TeacherBoard::query()->selectRaw('board_id as ref, COUNT(*) as total')->groupBy('board_id')->pluck('total', 'ref'),
                    'student profile' => StudentProfile::query()->selectRaw('board_id as ref, COUNT(*) as total')->groupBy('board_id')->pluck('total', 'ref'),
                    'booking' => Booking::query()->selectRaw('board_id as ref, COUNT(*) as total')->groupBy('board_id')->pluck('total', 'ref'),
                    'fee rule' => TeacherFee::query()->selectRaw('board_id as ref, COUNT(*) as total')->groupBy('board_id')->pluck('total', 'ref'),
                    'class package' => ClassPackage::query()->selectRaw('board_id as ref, COUNT(*) as total')->groupBy('board_id')->pluck('total', 'ref'),
                    'board variant' => BoardVariant::query()->selectRaw('board_id as ref, COUNT(*) as total')->groupBy('board_id')->pluck('total', 'ref'),
                ]),
                'subject' => $this->usageMap('subject_id', [
                    'teacher subject list' => TeacherSubject::query()->selectRaw('subject_id as ref, COUNT(*) as total')->groupBy('subject_id')->pluck('total', 'ref'),
                    'booking' => Booking::query()->selectRaw('subject_id as ref, COUNT(*) as total')->groupBy('subject_id')->pluck('total', 'ref'),
                    'fee rule' => TeacherFee::query()->selectRaw('subject_id as ref, COUNT(*) as total')->groupBy('subject_id')->pluck('total', 'ref'),
                    'class package' => ClassPackage::query()->selectRaw('subject_id as ref, COUNT(*) as total')->groupBy('subject_id')->pluck('total', 'ref'),
                ]),
                'class' => $this->usageMap('class_standard_id', [
                    'teacher class list' => TeacherClassStandard::query()->selectRaw('class_standard_id as ref, COUNT(*) as total')->groupBy('class_standard_id')->pluck('total', 'ref'),
                    'student profile' => StudentProfile::query()->selectRaw('class_standard_id as ref, COUNT(*) as total')->groupBy('class_standard_id')->pluck('total', 'ref'),
                    'booking' => Booking::query()->selectRaw('class_standard_id as ref, COUNT(*) as total')->groupBy('class_standard_id')->pluck('total', 'ref'),
                    'fee rule' => TeacherFee::query()->selectRaw('class_standard_id as ref, COUNT(*) as total')->groupBy('class_standard_id')->pluck('total', 'ref'),
                    'class package' => ClassPackage::query()->selectRaw('class_standard_id as ref, COUNT(*) as total')->groupBy('class_standard_id')->pluck('total', 'ref'),
                ]),
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Boards                                                              */
    /* ------------------------------------------------------------------ */

    public function storeBoard(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:boards,code'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:boards,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $board = Board::create([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'slug' => $this->uniqueSlug(Board::class, $validated['slug'] ?? null, $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->audit('catalog.board.create', Board::class, $board->id, [
            'name' => $board->name,
            'code' => $board->code,
        ]);

        return back()->with('success', 'Board "'.$board->name.'" has been created.');
    }

    public function updateBoard(Request $request, Board $board): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', Rule::unique('boards', 'code')->ignore($board->id)],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('boards', 'slug')->ignore($board->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $board->update([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'slug' => $this->uniqueSlug(Board::class, $validated['slug'] ?? null, $validated['name'], $board->id),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->audit('catalog.board.update', Board::class, $board->id, [
            'name' => $board->name,
            'code' => $board->code,
        ]);

        return back()->with('success', 'Board "'.$board->name.'" has been updated.');
    }

    public function destroyBoard(Board $board): RedirectResponse
    {
        $references = $this->boardReferences($board->id);

        if ($references !== []) {
            return back()->with('error', 'This board is still referenced by '.implode(', ', $references).'. Deactivate it instead of deleting it.');
        }

        try {
            $name = $board->name;
            $board->delete();
        } catch (QueryException) {
            return back()->with('error', 'This board is still in use by existing records, so it could not be deleted. Deactivate it instead.');
        }

        $this->audit('catalog.board.delete', Board::class, $board->id, ['name' => $name]);

        return back()->with('success', 'Board "'.$name.'" has been deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Subjects                                                            */
    /* ------------------------------------------------------------------ */

    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:subjects,code'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:subjects,slug'],
            'icon' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $subject = Subject::create([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'slug' => $this->uniqueSlug(Subject::class, $validated['slug'] ?? null, $validated['name']),
            'icon' => $validated['icon'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->audit('catalog.subject.create', Subject::class, $subject->id, [
            'name' => $subject->name,
            'code' => $subject->code,
        ]);

        return back()->with('success', 'Subject "'.$subject->name.'" has been created.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('subjects', 'slug')->ignore($subject->id)],
            'icon' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $subject->update([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'slug' => $this->uniqueSlug(Subject::class, $validated['slug'] ?? null, $validated['name'], $subject->id),
            'icon' => $validated['icon'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->audit('catalog.subject.update', Subject::class, $subject->id, [
            'name' => $subject->name,
            'code' => $subject->code,
        ]);

        return back()->with('success', 'Subject "'.$subject->name.'" has been updated.');
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        $references = $this->subjectReferences($subject->id);

        if ($references !== []) {
            return back()->with('error', 'This subject is still referenced by '.implode(', ', $references).'. Deactivate it instead of deleting it.');
        }

        try {
            $name = $subject->name;
            $subject->delete();
        } catch (QueryException) {
            return back()->with('error', 'This subject is still in use by existing records, so it could not be deleted. Deactivate it instead.');
        }

        $this->audit('catalog.subject.delete', Subject::class, $subject->id, ['name' => $name]);

        return back()->with('success', 'Subject "'.$name.'" has been deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Class standards                                                     */
    /* ------------------------------------------------------------------ */

    public function storeClass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:class_standards,code'],
            'level_order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'stage' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $class = ClassStandard::create([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'level_order' => (int) ($validated['level_order'] ?? 0),
            'stage' => $validated['stage'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->audit('catalog.class.create', ClassStandard::class, $class->id, [
            'name' => $class->name,
            'code' => $class->code,
        ]);

        return back()->with('success', 'Class standard "'.$class->name.'" has been created.');
    }

    public function updateClass(Request $request, ClassStandard $classStandard): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', Rule::unique('class_standards', 'code')->ignore($classStandard->id)],
            'level_order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'stage' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $classStandard->update([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'level_order' => (int) ($validated['level_order'] ?? 0),
            'stage' => $validated['stage'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $this->audit('catalog.class.update', ClassStandard::class, $classStandard->id, [
            'name' => $classStandard->name,
            'code' => $classStandard->code,
        ]);

        return back()->with('success', 'Class standard "'.$classStandard->name.'" has been updated.');
    }

    public function destroyClass(ClassStandard $classStandard): RedirectResponse
    {
        $references = $this->classReferences($classStandard->id);

        if ($references !== []) {
            return back()->with('error', 'This class standard is still referenced by '.implode(', ', $references).'. Deactivate it instead of deleting it.');
        }

        try {
            $name = $classStandard->name;
            $classStandard->delete();
        } catch (QueryException) {
            return back()->with('error', 'This class standard is still in use by existing records, so it could not be deleted. Deactivate it instead.');
        }

        $this->audit('catalog.class.delete', ClassStandard::class, $classStandard->id, ['name' => $name]);

        return back()->with('success', 'Class standard "'.$name.'" has been deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Build a slug that is unique inside the given table.
     *
     * @param  class-string  $model
     */
    private function uniqueSlug(string $model, ?string $slug, string $fallback, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $fallback) ?: 'item';
        $candidate = $base;
        $suffix = 1;

        while ($model::query()
            ->where('slug', $candidate)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $base.'-'.(++$suffix);
        }

        return $candidate;
    }

    /**
     * Turn grouped reference counts into a readable "id => reasons" map.
     *
     * @param  array<string, \Illuminate\Support\Collection<int, mixed>>  $sources
     * @return array<int, array<int, string>>
     */
    private function usageMap(string $key, array $sources): array
    {
        $map = [];

        foreach ($sources as $label => $counts) {
            foreach ($counts as $ref => $total) {
                $total = (int) $total;

                if ($total < 1) {
                    continue;
                }

                $map[(int) $ref][] = $total.' '.$label.($total === 1 ? '' : 's');
            }
        }

        return $map;
    }

    /**
     * @return array<int, string>
     */
    private function boardReferences(int $boardId): array
    {
        $checks = [
            'teacher board list' => TeacherBoard::where('board_id', $boardId)->count(),
            'student profile' => StudentProfile::where('board_id', $boardId)->count(),
            'booking' => Booking::where('board_id', $boardId)->count(),
            'fee rule' => TeacherFee::where('board_id', $boardId)->count(),
            'class package' => ClassPackage::where('board_id', $boardId)->count(),
            'board variant' => BoardVariant::where('board_id', $boardId)->count(),
        ];

        return $this->describeReferences($checks);
    }

    /**
     * @return array<int, string>
     */
    private function subjectReferences(int $subjectId): array
    {
        $checks = [
            'teacher subject list' => TeacherSubject::where('subject_id', $subjectId)->count(),
            'booking' => Booking::where('subject_id', $subjectId)->count(),
            'fee rule' => TeacherFee::where('subject_id', $subjectId)->count(),
            'class package' => ClassPackage::where('subject_id', $subjectId)->count(),
        ];

        return $this->describeReferences($checks);
    }

    /**
     * @return array<int, string>
     */
    private function classReferences(int $classId): array
    {
        $checks = [
            'teacher class list' => TeacherClassStandard::where('class_standard_id', $classId)->count(),
            'student profile' => StudentProfile::where('class_standard_id', $classId)->count(),
            'booking' => Booking::where('class_standard_id', $classId)->count(),
            'fee rule' => TeacherFee::where('class_standard_id', $classId)->count(),
            'class package' => ClassPackage::where('class_standard_id', $classId)->count(),
        ];

        return $this->describeReferences($checks);
    }

    /**
     * @param  array<string, int>  $checks
     * @return array<int, string>
     */
    private function describeReferences(array $checks): array
    {
        $references = [];

        foreach ($checks as $label => $count) {
            if ($count > 0) {
                $references[] = $count.' '.$label.($count === 1 ? '' : 's');
            }
        }

        return $references;
    }
}
