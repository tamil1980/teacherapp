<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Booking;
use App\Models\ClassStandard;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Parent accounts and the students linked to them.
 */
class AdminParentController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $parents = ParentProfile::query()
            ->with('user')
            ->withCount(['students', 'bookings'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', function ($user) use ($search) {
                    $user->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, ['active', 'suspended'], true), function ($query) use ($status) {
                $query->whereHas('user', fn ($user) => $user->where('status', $status));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.parents.index', [
            'parents' => $parents,
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function show(ParentProfile $parent): View
    {
        $parent->load('user');

        return view('admin.parents.show', [
            'parent' => $parent,
            'students' => StudentProfile::query()
                ->where('parent_profile_id', $parent->id)
                ->with(['classStandard', 'board'])
                ->orderBy('display_name')
                ->get(),
            'bookings' => Booking::query()
                ->where('parent_profile_id', $parent->id)
                ->with(['teacher.user', 'student', 'subject'])
                ->latest()
                ->limit(25)
                ->get(),
            'payments' => Payment::query()
                ->where('parent_profile_id', $parent->id)
                ->with('booking')
                ->latest()
                ->limit(25)
                ->get(),
            'totals' => [
                'paid' => round((float) Payment::query()
                    ->where('parent_profile_id', $parent->id)
                    ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_REFUNDED, Payment::STATUS_REFUNDED])
                    ->sum('amount'), 2),
                'refunded' => round((float) Payment::query()->where('parent_profile_id', $parent->id)->sum('refunded_amount'), 2),
                'bookings' => Booking::query()->where('parent_profile_id', $parent->id)->count(),
            ],
        ]);
    }

    /**
     * Suspend a parent account.
     */
    public function suspend(Request $request, ParentProfile $parent): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $parent->user?->update(['status' => 'suspended']);

        if ($parent->user) {
            $this->notifications->notify(
                $parent->user,
                'parent.suspended',
                'Your account has been suspended',
                'Reason: '.$validated['reason'].'. Please contact support if you believe this is a mistake.',
                ['parent_profile_id' => $parent->id]
            );
        }

        $this->audit('parent.suspend', ParentProfile::class, $parent->id, [
            'parent' => $parent->user?->name,
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', ($parent->user?->name ?? 'The parent').' has been suspended and can no longer log in.');
    }

    /**
     * Every student profile on the platform.
     *
     * Only the fields an administrator genuinely needs are exposed: the
     * display name, the class, the board and the linked parent. A child's
     * residential address, date of birth and medical notes are never shown.
     */
    public function students(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $classId = (string) $request->query('class', '');
        $boardId = (string) $request->query('board', '');

        $students = StudentProfile::query()
            ->with(['classStandard', 'board', 'parent.user'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('display_name', 'like', '%'.$search.'%');
            })
            ->when($classId !== '', fn ($query) => $query->where('class_standard_id', $classId))
            ->when($boardId !== '', fn ($query) => $query->where('board_id', $boardId))
            ->orderBy('display_name')
            ->paginate(25)
            ->withQueryString();

        $parentContacts = [];

        foreach ($students->getCollection() as $student) {
            $parentContacts[$student->id] = [
                'name' => $student->parent?->user?->name ?? '—',
                'contact' => $this->maskContact(
                    $student->parent?->user?->mobile,
                    $student->parent?->user?->email
                ),
            ];
        }

        return view('admin.students.index', [
            'students' => $students,
            'parentContacts' => $parentContacts,
            'filters' => ['q' => $search, 'class' => $classId, 'board' => $boardId],
            'classes' => ClassStandard::orderBy('level_order')->get(),
            'boards' => Board::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Mask a contact number so that a list view never leaks a full number.
     */
    private function maskContact(?string $mobile, ?string $email): string
    {
        if ($mobile) {
            $digits = preg_replace('/\D/', '', $mobile);

            if (strlen((string) $digits) >= 6) {
                return substr((string) $digits, 0, 2).'****'.substr((string) $digits, -3);
            }
        }

        if ($email) {
            [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
            $local = substr($local, 0, 2).'***';

            return $local.'@'.$domain;
        }

        return '—';
    }
}
