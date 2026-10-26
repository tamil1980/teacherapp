<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Models\TeacherDocument;
use App\Models\TeacherQualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Everything the teacher controls about their own public profile.
 *
 * The `verification_status` (and every other moderation field) is *never*
 * writable from this area — only an administrator can approve a teacher.
 */
class TeacherProfileController extends Controller
{
    /**
     * Languages a teacher may pick from.
     *
     * @var array<int, string>
     */
    public const LANGUAGES = [
        'English',
        'Hindi',
        'Tamil',
        'Telugu',
        'Malayalam',
        'Kannada',
        'Marathi',
        'Bengali',
        'Gujarati',
        'Punjabi',
        'Urdu',
        'Arabic',
        'French',
        'German',
    ];

    public const DOCUMENT_TYPES = [
        'identity' => 'Identity proof (Aadhaar / PAN / passport)',
        'address_proof' => 'Address proof',
        'qualification' => 'Qualification certificate',
        'police_clearance' => 'Police clearance certificate',
        'other' => 'Other supporting document',
    ];

    /**
     * The profile editor.
     */
    public function edit(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $teacher->load([
            'qualifications',
            'documents',
            'teacherSubjects.subject',
            'teacherBoards.board',
            'teacherClassStandards.classStandard',
            'teachingModes',
        ]);

        return view('teacher.profile.edit', [
            'teacher' => $teacher,
            'languages' => self::LANGUAGES,
            'documentTypes' => self::DOCUMENT_TYPES,
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'boards' => Board::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'classStandards' => ClassStandard::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the editable profile fields.
     */
    public function update(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'headline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'teaching_description' => ['nullable', 'string', 'max:5000'],
            'methodology' => ['nullable', 'string', 'max:5000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'qualification_summary' => ['nullable', 'string', 'max:255'],
            'languages' => ['nullable', 'array', 'max:14'],
            'languages.*' => ['string', Rule::in(self::LANGUAGES)],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'service_radius_km' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'cancellation_policy' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = collect($validated)->except('languages')->all();

        if (array_key_exists('languages', $validated)) {
            $attributes['languages'] = array_values($validated['languages'] ?? []);
        }

        // Moderation fields are intentionally not part of $attributes.
        $teacher->update($attributes);

        return back()->with('success', 'Your profile has been updated.');
    }

    /**
     * Replace the subject list.
     */
    public function updateSubjects(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'subjects' => ['required', 'array', 'min:1', 'max:30'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
        ]);

        $ids = collect($validated['subjects'])->map(fn ($id) => (int) $id)->unique()->values();

        $teacher->teacherSubjects()->delete();
        $teacher->teacherSubjects()->createMany($ids->map(fn ($id) => ['subject_id' => $id])->all());

        return back()->with('success', 'Your subjects have been updated.');
    }

    /**
     * Replace the board list.
     */
    public function updateBoards(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'boards' => ['required', 'array', 'min:1', 'max:30'],
            'boards.*' => ['integer', 'exists:boards,id'],
        ]);

        $ids = collect($validated['boards'])->map(fn ($id) => (int) $id)->unique()->values();

        $teacher->teacherBoards()->delete();
        $teacher->teacherBoards()->createMany($ids->map(fn ($id) => ['board_id' => $id])->all());

        return back()->with('success', 'Your boards have been updated.');
    }

    /**
     * Replace the class-standard list.
     */
    public function updateClasses(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'classes' => ['required', 'array', 'min:1', 'max:30'],
            'classes.*' => ['integer', 'exists:class_standards,id'],
        ]);

        $ids = collect($validated['classes'])->map(fn ($id) => (int) $id)->unique()->values();

        $teacher->teacherClassStandards()->delete();
        $teacher->teacherClassStandards()->createMany($ids->map(fn ($id) => ['class_standard_id' => $id])->all());

        return back()->with('success', 'The classes you teach have been updated.');
    }

    /**
     * Replace the teaching modes (online / offline).
     */
    public function updateModes(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'modes' => ['required', 'array', 'min:1', 'max:2'],
            'modes.*' => ['string', Rule::in(['online', 'offline'])],
        ]);

        $modes = collect($validated['modes'])->unique()->values();

        $teacher->teachingModes()->delete();
        $teacher->teachingModes()->createMany($modes->map(fn ($mode) => ['mode' => $mode])->all());

        $teacher->update([
            'accepts_online' => $modes->contains('online'),
            'accepts_offline' => $modes->contains('offline'),
        ]);

        return back()->with('success', 'Your teaching modes have been updated.');
    }

    /**
     * Upload a new profile photo.
     */
    public function updatePhoto(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        if ($teacher->photo_path) {
            Storage::disk('public')->delete($teacher->photo_path);
        }

        $path = $request->file('photo')->store('teachers/photos', 'public');

        $teacher->update(['photo_path' => $path]);

        return back()->with('success', 'Your photo has been updated.');
    }

    /**
     * Add a qualification (optionally with a scanned copy).
     */
    public function storeQualification(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'degree' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'board_university' => ['nullable', 'string', 'max:255'],
            'year_of_passing' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:'.(int) now()->year],
            'specialisation' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $documentPath = null;

        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('teachers/qualifications', 'public');
        }

        $teacher->qualifications()->create([
            'degree' => $validated['degree'],
            'institution' => $validated['institution'] ?? null,
            'board_university' => $validated['board_university'] ?? null,
            'year_of_passing' => $validated['year_of_passing'] ?? null,
            'specialisation' => $validated['specialisation'] ?? null,
            'description' => $validated['description'] ?? null,
            'document_path' => $documentPath,
            // A teacher can never self-verify — an administrator reviews it.
            'verification_status' => 'pending',
        ]);

        return back()->with('success', 'Your qualification has been added and is awaiting review.');
    }

    /**
     * Remove one of the teacher's qualifications.
     */
    public function destroyQualification(TeacherQualification $qualification)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $qualification->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own qualifications.');
        }

        if ($qualification->document_path) {
            Storage::disk('public')->delete($qualification->document_path);
        }

        $qualification->delete();

        return back()->with('success', 'The qualification has been removed.');
    }

    /**
     * Upload a supporting document for verification.
     */
    public function storeDocument(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $request->validate([
            'doc_type' => ['required', 'string', Rule::in(array_keys(self::DOCUMENT_TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('teachers/documents', 'public');

        $teacher->documents()->create([
            'doc_type' => $validated['doc_type'],
            'title' => $validated['title'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            // A teacher can never self-approve a document.
            'status' => 'pending',
        ]);

        return back()->with('success', 'Your document has been uploaded and is awaiting review.');
    }

    /**
     * Remove one of the teacher's documents.
     */
    public function destroyDocument(TeacherDocument $document)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $document->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own documents.');
        }

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'The document has been removed.');
    }
}
