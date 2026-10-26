<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TeacherDocument;
use App\Models\TeacherEarning;
use App\Models\TeacherProfile;
use App\Services\EarningService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Teacher verification, moderation and suspension.
 */
class AdminTeacherController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $city = trim((string) $request->query('city', ''));

        $teachers = TeacherProfile::query()
            ->with(['user', 'teacherSubjects.subject', 'teacherBoards.board'])
            ->withCount(['bookings', 'documents', 'qualifications'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('verification_status', $status))
            ->when($city !== '', fn ($query) => $query->where('city', 'like', '%'.$city.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'filters' => ['q' => $search, 'status' => $status, 'city' => $city],
            'statusCounts' => TeacherProfile::query()
                ->selectRaw('verification_status, COUNT(*) as aggregate')
                ->groupBy('verification_status')
                ->pluck('aggregate', 'verification_status'),
        ]);
    }

    public function show(TeacherProfile $teacher): View
    {
        $teacher->load([
            'user',
            'qualifications',
            'documents',
            'fees.subject',
            'fees.classStandard',
            'fees.board',
            'availabilityRules',
            'teacherSubjects.subject',
            'teacherBoards.board',
            'teacherClassStandards.classStandard',
            'teachingModes',
            'locations',
        ]);

        return view('admin.teachers.show', [
            'teacher' => $teacher,
            'earnings' => TeacherEarning::query()
                ->where('teacher_profile_id', $teacher->id)
                ->with('booking')
                ->latest()
                ->limit(25)
                ->get(),
            'earningsSummary' => app(EarningService::class)->summary($teacher),
            'bookings' => Booking::query()
                ->where('teacher_profile_id', $teacher->id)
                ->with(['student', 'parent.user', 'subject', 'board', 'classStandard'])
                ->latest()
                ->limit(25)
                ->get(),
            'bookingCounts' => Booking::query()
                ->where('teacher_profile_id', $teacher->id)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }

    /**
     * Approve a teacher so that they become visible in the directory.
     */
    public function verify(TeacherProfile $teacher): RedirectResponse
    {
        $previousStatus = $teacher->verification_status;

        $teacher->update([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        $this->notifyTeacher(
            $teacher,
            'teacher.verified',
            'Your profile has been verified',
            'Welcome aboard! Your teacher profile has been verified and you are now listed in the teacher directory.'
        );

        $this->audit('teacher.verify', TeacherProfile::class, $teacher->id, [
            'teacher' => $teacher->user?->name,
            'previous_status' => $previousStatus,
        ]);

        return back()->with('success', $teacher->user?->name.' has been verified and can now accept bookings.');
    }

    /**
     * Reject a teacher application with a reason.
     */
    public function reject(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $teacher->update([
            'verification_status' => 'rejected',
            'verified_at' => null,
            'verified_by' => auth()->id(),
            'rejection_reason' => $validated['reason'],
        ]);

        $this->notifyTeacher(
            $teacher,
            'teacher.rejected',
            'Your teacher application was not approved',
            'Reason: '.$validated['reason'].'. Please update your profile and contact support if you need help.'
        );

        $this->audit('teacher.reject', TeacherProfile::class, $teacher->id, [
            'teacher' => $teacher->user?->name,
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', $teacher->user?->name.' has been rejected and has been notified.');
    }

    /**
     * Suspend the teacher's login.
     */
    public function suspend(Request $request, TeacherProfile $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $teacher->user?->update(['status' => 'suspended']);

        $this->notifyTeacher(
            $teacher,
            'teacher.suspended',
            'Your account has been suspended',
            'Reason: '.$validated['reason'].'. Please contact support to get your account restored.'
        );

        $this->audit('teacher.suspend', TeacherProfile::class, $teacher->id, [
            'teacher' => $teacher->user?->name,
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', $teacher->user?->name.' has been suspended and can no longer log in.');
    }

    /**
     * Restore a suspended teacher.
     */
    public function reactivate(TeacherProfile $teacher): RedirectResponse
    {
        $teacher->user?->update(['status' => 'active']);

        $this->notifyTeacher(
            $teacher,
            'teacher.reactivated',
            'Your account has been reactivated',
            'You can log in and accept bookings again. Welcome back!'
        );

        $this->audit('teacher.reactivate', TeacherProfile::class, $teacher->id, [
            'teacher' => $teacher->user?->name,
        ]);

        return back()->with('success', $teacher->user?->name.' has been reactivated.');
    }

    /**
     * Approve or reject one uploaded teacher document.
     */
    public function reviewDocument(Request $request, TeacherDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.in' => 'Choose either approve or reject.',
        ]);

        if ($validated['status'] === 'rejected' && blank($validated['remarks'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['remarks' => 'Please tell the teacher why the document was rejected.']);
        }

        $document->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->notifications->notify(
            $document->teacher?->user,
            'teacher.document.reviewed',
            'Document '.($validated['status'] === 'approved' ? 'approved' : 'rejected'),
            'The document "'.$document->title.'" was '.$validated['status'].($validated['remarks'] ? ' — '.$validated['remarks'] : '.'),
            ['teacher_profile_id' => $document->teacher_profile_id, 'document_id' => $document->id]
        );

        $this->audit('teacher.document.review', TeacherDocument::class, $document->id, [
            'teacher_profile_id' => $document->teacher_profile_id,
            'document' => $document->title,
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'The document "'.$document->title.'" was '.$validated['status'].'.');
    }

    /**
     * Notify a teacher, tolerating a missing (deleted) user account.
     */
    private function notifyTeacher(TeacherProfile $teacher, string $type, string $title, string $message): void
    {
        $user = $teacher->user;

        if (! $user) {
            return;
        }

        $this->notifications->notify(
            $user,
            $type,
            $title,
            $message,
            ['teacher_profile_id' => $teacher->id]
        );
    }
}
