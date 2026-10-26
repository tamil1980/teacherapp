<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\StudentProfile;
use Illuminate\View\View;

class ParentDashboardController extends Controller
{
    public function index(): View
    {
        $parent = $this->parentProfile();

        $students = $parent->students()->with(['classStandard', 'board'])->get();

        $upcoming = Booking::where('parent_profile_id', $parent->id)
            ->where('start_at', '>=', now())
            ->whereIn('status', [Booking::STATUS_CONFIRMED, Booking::STATUS_PENDING_TEACHER, Booking::STATUS_ONGOING])
            ->with(['teacher.user', 'subject', 'classStandard', 'board'])
            ->orderBy('start_at')
            ->take(6)
            ->get();

        $recent = Booking::where('parent_profile_id', $parent->id)
            ->with(['teacher.user', 'subject'])
            ->latest()
            ->take(5)
            ->get();

        $completedCount = Booking::where('parent_profile_id', $parent->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->count();

        $totalSpent = (float) Payment::where('parent_profile_id', $parent->id)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED, Payment::STATUS_PARTIALLY_REFUNDED])
            ->sum('amount');

        $pendingReviews = Booking::where('parent_profile_id', $parent->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->whereDoesntHave('review')
            ->count();

        return view('parent.dashboard', compact(
            'students',
            'upcoming',
            'recent',
            'completedCount',
            'totalSpent',
            'pendingReviews'
        ));
    }
}
