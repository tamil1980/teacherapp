<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherEarning;
use App\Services\EarningService;
use App\Support\Platform;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The teacher's earnings ledger and payout records.
 *
 * A payout is only ever a *record* — the money is moved by the platform
 * administrator. Nothing on these pages claims that a transfer has happened.
 */
class TeacherEarningsController extends Controller
{
    public const PAYOUT_METHODS = [
        'bank_transfer' => 'Bank transfer',
        'upi' => 'UPI',
    ];

    public function __construct(private readonly EarningService $earnings) {}

    /**
     * Earnings summary, the ledger and the payout records.
     */
    public function index(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $summary = $this->earnings->summary($teacher);

        $rows = TeacherEarning::query()
            ->where('teacher_profile_id', $teacher->id)
            ->with(['booking', 'payout'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $payouts = $teacher->payouts()
            ->with('items')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $availableEarnings = $this->earnings->availableEarnings($teacher);
        $availableBalance = round($availableEarnings->sum(fn (TeacherEarning $earning) => (float) $earning->net_amount), 2);

        return view('teacher.earnings.index', [
            'teacher' => $teacher,
            'summary' => $summary,
            'earnings' => $rows,
            'payouts' => $payouts,
            'availableBalance' => $availableBalance,
            'availableCount' => $availableEarnings->count(),
            'minimumPayout' => (float) Platform::get('payout_minimum_amount', 500),
            'processingDays' => (int) Platform::get('payout_processing_days', 3),
            'settlementDelayDays' => (int) Platform::get('teacher_settlement_delay_days', 2),
            'methods' => self::PAYOUT_METHODS,
        ]);
    }

    /**
     * A single earning with its commission breakdown.
     */
    public function show(TeacherEarning $earning)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher || $earning->teacher_profile_id !== $teacher->id) {
            abort(403, 'This earning does not belong to you.');
        }

        $earning->load(['booking.subject', 'booking.classStandard', 'booking.board', 'payout', 'payout.items']);

        $commission = $earning->booking?->commissions()->first();

        return view('teacher.earnings.show', [
            'teacher' => $teacher,
            'earning' => $earning,
            'commission' => $commission,
        ]);
    }

    /**
     * Create a payout record for the available balance.
     */
    public function requestPayout(Request $request)
    {
        $teacher = $this->teacherProfile();

        if (! $teacher) {
            abort(403, 'Your teacher profile could not be found.');
        }

        $availableEarnings = $this->earnings->availableEarnings($teacher);
        $availableBalance = round($availableEarnings->sum(fn (TeacherEarning $earning) => (float) $earning->net_amount), 2);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', Rule::in(array_keys(self::PAYOUT_METHODS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $validated['amount'], 2);

        if ($availableBalance <= 0) {
            return back()->withInput()
                ->withErrors(['amount' => 'There is no balance available for payout at the moment.']);
        }

        if ($amount > $availableBalance) {
            return back()->withInput()->withErrors([
                'amount' => 'The requested amount is higher than your available balance of '.$this->money($availableBalance).'.',
            ]);
        }

        try {
            $payout = $this->earnings->createPayout(
                $teacher,
                $amount,
                $validated['method'],
                $validated['notes'] ?? null
            );
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Payout record '.$payout->reference_no.' has been created for '.$this->money((float) $payout->amount).'. The platform administrator will process it.');
    }
}
