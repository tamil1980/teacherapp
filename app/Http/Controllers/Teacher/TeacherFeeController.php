<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\ClassPackage;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Models\TeacherFee;
use App\Support\Platform;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The teacher-controlled price list.
 *
 * IMPORTANT: changing a fee only affects *future* bookings. Every booking
 * stores its own agreed price snapshot (`bookings.hourly_rate`), so an
 * already confirmed booking always keeps the price that was agreed with the
 * parent. Fee changes therefore never touch existing booking rows.
 */
class TeacherFeeController extends Controller
{
    public const MODES = [
        'online' => 'Online',
        'offline' => 'Offline',
        'both' => 'Online & offline',
    ];

    /**
     * "Manage Teaching Fees" screen.
     */
    public function index(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $fees = $teacher->fees()
            ->with(['subject', 'classStandard', 'board'])
            ->orderBy('mode')
            ->orderByDesc('id')
            ->get();

        return view('teacher.fees.index', [
            'teacher' => $teacher,
            'fees' => $fees,
            'modes' => self::MODES,
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'classStandards' => ClassStandard::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'boards' => Board::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'suggestedRate' => (float) Platform::get('suggested_hourly_rate', 500),
            'defaultSessionMinutes' => (int) Platform::get('default_session_minutes', 60),
        ]);
    }

    /**
     * Add a fee row.
     */
    public function store(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $this->validateFee($request);

        $teacher->fees()->create([
            'subject_id' => $validated['subject_id'] ?? null,
            'class_standard_id' => $validated['class_standard_id'] ?? null,
            'board_id' => $validated['board_id'] ?? null,
            'mode' => $validated['mode'],
            'hourly_rate' => $validated['hourly_rate'],
            'session_minutes' => (int) $validated['session_minutes'],
            'travel_charge' => $validated['travel_charge'] ?? 0,
            'effective_from' => $validated['effective_from'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'The teaching fee has been added. New bookings will use this rate.');
    }

    /**
     * Update a fee row.
     */
    public function update(Request $request, TeacherFee $fee)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $fee->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only edit your own teaching fees.');
        }

        $validated = $this->validateFee($request);

        $fee->update([
            'subject_id' => $validated['subject_id'] ?? null,
            'class_standard_id' => $validated['class_standard_id'] ?? null,
            'board_id' => $validated['board_id'] ?? null,
            'mode' => $validated['mode'],
            'hourly_rate' => $validated['hourly_rate'],
            'session_minutes' => (int) $validated['session_minutes'],
            'travel_charge' => $validated['travel_charge'] ?? 0,
            'effective_from' => $validated['effective_from'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        // Existing bookings keep their agreed price — nothing is rewritten here.
        return back()->with('success', 'The teaching fee has been updated. Bookings already confirmed keep their original agreed price.');
    }

    /**
     * Remove a fee row.
     */
    public function destroy(TeacherFee $fee)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $fee->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own teaching fees.');
        }

        $fee->delete();

        return back()->with('success', 'The teaching fee has been removed.');
    }

    /**
     * Class packages (bundles of sessions).
     */
    public function packages(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $packages = ClassPackage::query()
            ->where('teacher_profile_id', $teacher->id)
            ->with(['subject', 'classStandard', 'board'])
            ->orderByDesc('id')
            ->get();

        return view('teacher.packages.index', [
            'teacher' => $teacher,
            'packages' => $packages,
            'modes' => self::MODES,
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'classStandards' => ClassStandard::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'boards' => Board::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'suggestedRate' => (float) Platform::get('suggested_hourly_rate', 500),
            'defaultSessionMinutes' => (int) Platform::get('default_session_minutes', 60),
        ]);
    }

    /**
     * Add a class package.
     */
    public function storePackage(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'mode' => ['required', 'string', Rule::in(array_keys(self::MODES))],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:200'],
            'session_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'price_per_session' => ['required', 'numeric', 'min:1', 'max:100000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $sessions = (int) $validated['total_sessions'];
        $perSession = (float) $validated['price_per_session'];

        ClassPackage::create([
            'teacher_profile_id' => $teacher->id,
            'name' => $validated['name'],
            'subject_id' => $validated['subject_id'] ?? null,
            'class_standard_id' => $validated['class_standard_id'] ?? null,
            'board_id' => $validated['board_id'] ?? null,
            'mode' => $validated['mode'],
            'total_sessions' => $sessions,
            'session_minutes' => (int) $validated['session_minutes'],
            'price_per_session' => $perSession,
            'total_price' => round($perSession * $sessions, 2),
            'description' => $validated['description'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_to' => $validated['valid_to'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'The class package has been added.');
    }

    /**
     * Remove a class package.
     */
    public function destroyPackage(ClassPackage $package)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $package->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own class packages.');
        }

        $package->delete();

        return back()->with('success', 'The class package has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateFee(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'class_standard_id' => ['nullable', 'integer', 'exists:class_standards,id'],
            'board_id' => ['nullable', 'integer', 'exists:boards,id'],
            'mode' => ['required', 'string', Rule::in(array_keys(self::MODES))],
            'hourly_rate' => ['required', 'numeric', 'min:1', 'max:100000'],
            'session_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'travel_charge' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'effective_from' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
