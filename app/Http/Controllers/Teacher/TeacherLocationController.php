<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The places a teacher travels to teach (home, student home, tuition centre).
 */
class TeacherLocationController extends Controller
{
    public const TYPES = [
        'teacher_location' => 'My own teaching location',
        'student_location' => 'Student’s home',
        'tuition_centre' => 'Tuition centre',
    ];

    /**
     * List every location of the teacher.
     */
    public function index(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $locations = $teacher->locations()
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();

        return view('teacher.locations.index', [
            'teacher' => $teacher,
            'locations' => $locations,
            'types' => self::TYPES,
        ]);
    }

    /**
     * Add a location.
     */
    public function store(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $validated = $this->validateLocation($request);

        $teacher->locations()->create($this->payload($validated));

        return back()->with('success', 'The teaching location has been added.');
    }

    /**
     * Update one of the teacher's locations.
     */
    public function update(Request $request, TeacherLocation $location)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $location->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only edit your own teaching locations.');
        }

        $validated = $this->validateLocation($request);

        $location->update($this->payload($validated));

        return back()->with('success', 'The teaching location has been updated.');
    }

    /**
     * Remove a location (soft deleted so old bookings keep their reference).
     */
    public function destroy(TeacherLocation $location)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $location->teacher_profile_id !== $teacher->id) {
            abort(403, 'You may only remove your own teaching locations.');
        }

        $wasDefault = $location->is_default;

        $location->delete();

        if ($wasDefault) {
            // Promote another active location so the teacher always has one default.
            $teacher->locations()
                ->where('is_active', true)
                ->orderBy('id')
                ->first()
                ?->update(['is_default' => true]);
        }

        return back()->with('success', 'The teaching location has been removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateLocation(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'travel_charge' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Normalise the validated payload and enforce a single default location.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function payload(array $validated): array
    {
        $isDefault = (bool) ($validated['is_default'] ?? false);

        $payload = [
            'label' => $validated['label'],
            'type' => $validated['type'],
            'address_line1' => $validated['address_line1'] ?? null,
            'area' => $validated['area'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'travel_charge' => $validated['travel_charge'] ?? 0,
            'is_default' => $isDefault,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ];

        if ($isDefault) {
            $this->clearOtherDefaults();
        }

        return $payload;
    }

    /**
     * Only one location may carry the default flag.
     */
    protected function clearOtherDefaults(): void
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            return;
        }

        $teacher->locations()->where('is_default', true)->update(['is_default' => false]);
    }
}
