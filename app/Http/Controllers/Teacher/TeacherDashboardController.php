<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\TeacherEarning;
use App\Services\AvailabilityService;
use App\Services\EarningService;
use Illuminate\Http\Request;

/**
 * Read-only landing page for the teacher area.
 */
class TeacherDashboardController extends Controller
{
    public function index(Request $request, EarningService $earnings, AvailabilityService $availability)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $upcomingStatuses = [Booking::STATUS_PENDING_TEACHER, Booking::STATUS_CONFIRMED, Booking::STATUS_ONGOING];

        $upcomingCount = Booking::where('teacher_profile_id', $teacher->id)
            ->whereIn('status', $upcomingStatuses)
            ->where('start_at', '>=', $now)
            ->count();

        $pendingCount = Booking::where('teacher_profile_id', $teacher->id)
            ->where('status', Booking::STATUS_PENDING_TEACHER)
            ->count();

        $monthEarnings = (float) TeacherEarning::where('teacher_profile_id', $teacher->id)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('net_amount');

        $completedCount = Booking::where('teacher_profile_id', $teacher->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->count();

        $relations = ['student', 'subject', 'classStandard', 'board'];

        $todayBookings = Booking::with($relations)
            ->where('teacher_profile_id', $teacher->id)
            ->whereBetween('start_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->orderBy('start_at')
            ->get();

        $upcomingBookings = Booking::with($relations)
            ->where('teacher_profile_id', $teacher->id)
            ->where('start_at', '>=', $now)
            ->whereIn('status', $upcomingStatuses)
            ->orderBy('start_at')
            ->limit(8)
            ->get();

        $recentBookings = Booking::with($relations)
            ->where('teacher_profile_id', $teacher->id)
            ->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $notifications = AppNotification::where('user_id', $teacher->user_id)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $unreadNotifications = AppNotification::where('user_id', $teacher->user_id)
            ->whereNull('read_at')
            ->count();

        $summary = $earnings->summary($teacher);

        $availableSlots = 0;
        for ($day = $now->copy()->startOfDay(); $day->lte($now->copy()->addDays(6)->endOfDay()); $day->addDay()) {
            $availableSlots += $availability->slotsForDate($teacher, $day)
                ->where('status', AvailabilityService::STATUS_AVAILABLE)
                ->count();
        }

        $quickLinks = [
            ['route' => 'teacher.availability.index', 'label' => 'Set availability', 'hint' => 'Weekly hours, one-off slots and holidays'],
            ['route' => 'teacher.fees.index', 'label' => 'Manage teaching fees', 'hint' => 'Hourly rates per subject, class and mode'],
            ['route' => 'teacher.packages.index', 'label' => 'Class packages', 'hint' => 'Bundle sessions at a fixed per-session price'],
            ['route' => 'teacher.locations.index', 'label' => 'Teaching locations', 'hint' => 'Where you travel to teach'],
            ['route' => 'teacher.profile.edit', 'label' => 'Edit profile', 'hint' => 'Bio, photo, qualifications and documents'],
            ['route' => 'teacher.earnings.index', 'label' => 'Earnings & payouts', 'hint' => 'Session ledger and payout records'],
        ];

        return view('teacher.dashboard', [
            'teacher' => $teacher,
            'upcomingCount' => $upcomingCount,
            'pendingCount' => $pendingCount,
            'monthEarnings' => $monthEarnings,
            'completedCount' => $completedCount,
            'todayBookings' => $todayBookings,
            'upcomingBookings' => $upcomingBookings,
            'recentBookings' => $recentBookings,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
            'summary' => $summary,
            'availableSlots' => $availableSlots,
            'quickLinks' => $quickLinks,
        ]);
    }
}
