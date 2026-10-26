<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Refunds against captured payments.
 */
class AdminRefundController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $refunds = Refund::query()
            ->with(['payment.booking.teacher.user', 'payment.parent.user'])
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('reference_no', 'like', '%'.$filters['q'].'%')
                        ->orWhereHas('payment', fn ($p) => $p->where('reference_no', 'like', '%'.$filters['q'].'%'));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'filters' => $filters,
            'totals' => [
                'count' => Refund::query()->count(),
                'amount' => round((float) Refund::query()->sum('amount'), 2),
                'pending' => Refund::query()->whereIn('status', ['pending', 'processing'])->count(),
            ],
        ]);
    }

    /**
     * Issue a full or partial refund against a payment.
     */
    public function store(Request $request, Payment $payment): RedirectResponse
    {
        $refundable = round($payment->refundableAmount(), 2);

        if (! $payment->isPaid()) {
            return back()->with('error', 'Only a captured payment can be refunded.');
        }

        if ($refundable <= 0.01) {
            return back()->with('error', 'This payment has already been refunded in full.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$refundable],
            'reason' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'requested_by' => ['nullable', 'in:admin,parent'],
        ], [
            'amount.max' => 'The refund cannot be more than the refundable amount of ₹'.number_format($refundable, 2).'.',
        ]);

        $requestedBy = auth()->id();

        if (($validated['requested_by'] ?? 'admin') === 'parent') {
            $requestedBy = $payment->parent?->user_id ?? $requestedBy;
        }

        try {
            $refund = $this->payments->refund(
                $payment,
                round((float) $validated['amount'], 2),
                $validated['reason'],
                $requestedBy,
                auth()->id(),
                $validated['notes'] ?? null
            );
        } catch (PaymentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        $this->audit('payment.refund', Refund::class, $refund->id, [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->reference_no,
            'amount' => (float) $refund->amount,
            'reason' => $refund->reason,
            'requested_by' => $requestedBy,
            'processed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Refund '.$refund->reference_no.' for ₹'.number_format((float) $refund->amount, 2).' has been processed.');
    }
}
