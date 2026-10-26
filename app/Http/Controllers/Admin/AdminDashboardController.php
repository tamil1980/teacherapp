<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Models\Refund;
use App\Models\StudentProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\TeacherProfile;
use Illuminate\View\View;

/**
 * The platform health dashboard.
 */
class AdminDashboardController extends Controller
{
    use AuditsActions;

    public function index(): View
    {
        $teacherCounts = TeacherProfile::query()
            ->selectRaw('verification_status, COUNT(*) as aggregate')
            ->groupBy('verification_status')
            ->pluck('aggregate', 'verification_status');

        $bookingCounts = Booking::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $capturedStatuses = [Payment::STATUS_PAID, Payment::STATUS_PARTIALLY_REFUNDED, Payment::STATUS_REFUNDED];

        $grossRevenue = round((float) Payment::whereIn('status', $capturedStatuses)->sum('amount'), 2);
        $refundedTotal = round((float) Payment::sum('refunded_amount'), 2);
        $commissionEarned = round((float) PlatformCommission::sum('platform_amount'), 2);
        $teacherOwed = round((float) TeacherEarning::whereIn('status', [
            TeacherEarning::STATUS_PENDING,
            TeacherEarning::STATUS_AVAILABLE,
        ])->sum('net_amount'), 2);

        $pendingRefunds = Refund::query()->whereIn('status', ['pending', 'processing']);
        $pendingPayouts = TeacherPayout::query()->whereIn('status', [
            TeacherPayout::STATUS_REQUESTED,
            TeacherPayout::STATUS_PROCESSING,
        ]);

        $revenueByMonth = $this->revenueByMonth();

        return view('admin.dashboard', [
            'teacherTotal' => (int) $teacherCounts->sum(),
            'teacherCounts' => [
                'pending' => (int) ($teacherCounts['pending'] ?? 0),
                'approved' => (int) ($teacherCounts['approved'] ?? 0),
                'rejected' => (int) ($teacherCounts['rejected'] ?? 0),
            ],
            'parentTotal' => ParentProfile::count(),
            'studentTotal' => StudentProfile::count(),
            'bookingTotal' => (int) $bookingCounts->sum(),
            'bookingCounts' => $bookingCounts->map(fn ($v) => (int) $v)->all(),
            'grossRevenue' => $grossRevenue,
            'refundedTotal' => $refundedTotal,
            'netRevenue' => round($grossRevenue - $refundedTotal, 2),
            'commissionEarned' => $commissionEarned,
            'teacherOwed' => $teacherOwed,
            'pendingRefundCount' => (clone $pendingRefunds)->count(),
            'pendingRefundAmount' => round((float) (clone $pendingRefunds)->sum('amount'), 2),
            'pendingPayoutCount' => (clone $pendingPayouts)->count(),
            'pendingPayoutAmount' => round((float) (clone $pendingPayouts)->sum('amount'), 2),
            'openDisputeCount' => Dispute::query()->whereIn('status', ['open', 'in_progress'])->count(),
            'recentBookings' => Booking::with(['teacher.user', 'parent.user', 'student', 'subject'])
                ->latest()
                ->limit(10)
                ->get(),
            'recentAudits' => AdminAuditLog::with('admin')->latest()->limit(8)->get(),
            'revenueByMonth' => $revenueByMonth['months'],
            'revenueMax' => $revenueByMonth['max'],
            'revenueTotal' => $revenueByMonth['total'],
        ]);
    }

    /**
     * Captured revenue grouped into the last six calendar months.
     *
     * The grouping is done in PHP so the chart works on every database
     * driver without relying on dialect specific date functions.
     *
     * @return array{months: array<int, array{label: string, total: float}>, max: float, total: float}
     */
    private function revenueByMonth(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->subMonths($offset)->startOfMonth());

        $payments = Payment::query()
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['paid_at', 'amount']);

        $rows = $months->map(function ($month) use ($payments) {
            $total = $payments
                ->filter(fn ($payment) => $payment->paid_at !== null && $payment->paid_at->isSameMonth($month))
                ->sum(fn ($payment) => (float) $payment->amount);

            return [
                'label' => $month->format('M Y'),
                'total' => round($total, 2),
            ];
        })->values()->all();

        $max = 0.0;
        $total = 0.0;

        foreach ($rows as $row) {
            $max = max($max, $row['total']);
            $total += $row['total'];
        }

        return [
            'months' => $rows,
            'max' => $max > 0 ? $max : 1.0,
            'total' => round($total, 2),
        ];
    }
}
