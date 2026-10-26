<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\TeacherProfile;
use App\Services\EarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Teacher payouts.
 *
 * A payout row is a *record* of money that has been (or is about to be)
 * transferred outside of EduBook. Creating one marks the underlying earnings
 * as paid in the ledger — it does not move any money by itself.
 */
class AdminPayoutController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly EarningService $earnings) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $payouts = TeacherPayout::query()
            ->with('teacher.user')
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where('reference_no', 'like', '%'.$filters['q'].'%');
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.payouts.index', [
            'payouts' => $payouts,
            'filters' => $filters,
            'balances' => $this->balances(),
            'totals' => [
                'requested' => (int) TeacherPayout::query()->whereIn('status', [
                    TeacherPayout::STATUS_REQUESTED,
                    TeacherPayout::STATUS_PROCESSING,
                ])->count(),
                'requested_amount' => round((float) TeacherPayout::query()->whereIn('status', [
                    TeacherPayout::STATUS_REQUESTED,
                    TeacherPayout::STATUS_PROCESSING,
                ])->sum('amount'), 2),
                'completed_amount' => round((float) TeacherPayout::query()->where('status', TeacherPayout::STATUS_COMPLETED)->sum('amount'), 2),
            ],
        ]);
    }

    public function show(TeacherPayout $payout): View
    {
        $payout->load(['teacher.user', 'items.earning.booking']);

        return view('admin.payouts.show', [
            'payout' => $payout,
        ]);
    }

    /**
     * Create a payout record for a teacher from their available earnings.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_profile_id' => ['required', 'integer', Rule::exists('teacher_profiles', 'id')],
            'amount' => ['nullable', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(['bank_transfer', 'upi', 'cheque'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $teacher = TeacherProfile::findOrFail($validated['teacher_profile_id']);

        $summary = $this->earnings->summary($teacher);
        $available = (float) $summary['available'];

        if ($available <= 0) {
            return back()
                ->withInput()
                ->with('error', 'This teacher has no earnings available for payout yet.');
        }

        if (isset($validated['amount']) && (float) $validated['amount'] > $available) {
            return back()
                ->withInput()
                ->with('error', 'The payout cannot be more than the available balance of ₹'.number_format($available, 2).'.');
        }

        // Any earnings whose settlement delay has elapsed become payable.
        $released = $this->earnings->releaseDueEarnings();

        try {
            $payout = $this->earnings->createPayout(
                $teacher,
                isset($validated['amount']) ? round((float) $validated['amount'], 2) : null,
                $validated['method'],
                $validated['notes'] ?? null,
                auth()->id()
            );
        } catch (\RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        $this->audit('payout.create', TeacherPayout::class, $payout->id, [
            'teacher_profile_id' => $teacher->id,
            'teacher' => $teacher->user?->name,
            'amount' => (float) $payout->amount,
            'method' => $payout->method,
            'released_earnings' => $released,
        ]);

        return back()->with('success', 'Payout '.$payout->reference_no.' for ₹'.number_format((float) $payout->amount, 2).' has been recorded. Transfer the money outside EduBook and then mark it as completed.');
    }

    /**
     * Mark a payout as completed once the money has actually moved.
     */
    public function complete(Request $request, TeacherPayout $payout): RedirectResponse
    {
        $validated = $request->validate([
            'gateway_payout_id' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'gateway_payout_id.required' => 'Enter the bank or gateway reference for the transfer.',
        ]);

        if ($payout->status === TeacherPayout::STATUS_COMPLETED) {
            return back()->with('error', 'This payout has already been marked as completed.');
        }

        $payout->update([
            'status' => TeacherPayout::STATUS_COMPLETED,
            'gateway_payout_id' => $validated['gateway_payout_id'],
            'notes' => $validated['notes'] ?? $payout->notes,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $this->audit('payout.complete', TeacherPayout::class, $payout->id, [
            'teacher_profile_id' => $payout->teacher_profile_id,
            'amount' => (float) $payout->amount,
            'gateway_payout_id' => $validated['gateway_payout_id'],
        ]);

        return back()->with('success', 'Payout '.$payout->reference_no.' has been marked as completed.');
    }

    /**
     * Per-teacher available and pending balances.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function balances(): \Illuminate\Support\Collection
    {
        $available = TeacherEarning::query()
            ->select('teacher_profile_id')
            ->selectRaw('SUM(net_amount) as total')
            ->where('status', TeacherEarning::STATUS_AVAILABLE)
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->groupBy('teacher_profile_id')
            ->having('total', '>', 0)
            ->pluck('total', 'teacher_profile_id');

        $pending = TeacherEarning::query()
            ->select('teacher_profile_id')
            ->selectRaw('SUM(net_amount) as total')
            ->where('status', TeacherEarning::STATUS_PENDING)
            ->groupBy('teacher_profile_id')
            ->pluck('total', 'teacher_profile_id');

        return TeacherProfile::query()
            ->with('user')
            ->whereIn('id', $available->keys()->merge($pending->keys())->unique())
            ->get()
            ->map(fn (TeacherProfile $teacher) => (object) [
                'teacher' => $teacher,
                'available' => round((float) ($available[$teacher->id] ?? 0), 2),
                'pending' => round((float) ($pending[$teacher->id] ?? 0), 2),
            ])
            ->sortByDesc('available')
            ->values();
    }
}
