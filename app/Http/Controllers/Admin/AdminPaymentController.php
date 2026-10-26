<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuditsActions;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Payment monitoring.
 */
class AdminPaymentController extends Controller
{
    use AuditsActions;

    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => (string) $request->query('status', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $query = Payment::query()
            ->with(['booking.teacher.user', 'parent.user'])
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('reference_no', 'like', '%'.$filters['q'].'%')
                        ->orWhere('invoice_number', 'like', '%'.$filters['q'].'%')
                        ->orWhere('gateway_payment_id', 'like', '%'.$filters['q'].'%');
                });
            });

        $totals = [
            'count' => (clone $query)->count(),
            'amount' => round((float) (clone $query)->sum('amount'), 2),
            'refunded' => round((float) (clone $query)->sum('refunded_amount'), 2),
            'gateway_fee' => round((float) (clone $query)->sum('gateway_fee'), 2),
            'tax' => round((float) (clone $query)->sum('tax_amount'), 2),
        ];

        $payments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['booking.teacher.user', 'booking.parent.user', 'booking.student', 'parent.user', 'refunds']);

        $events = PaymentWebhookEvent::query()
            ->where(function ($query) use ($payment) {
                $query->where('payment_id', $payment->id);

                if ($payment->gateway_order_id) {
                    $query->orWhere('gateway_order_id', $payment->gateway_order_id);
                }

                if ($payment->gateway_payment_id) {
                    $query->orWhere('gateway_payment_id', $payment->gateway_payment_id);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        return view('admin.payments.show', [
            'payment' => $payment,
            'events' => $events,
            'refunds' => $payment->refunds,
            'receipt' => $this->payments->receipt($payment),
        ]);
    }
}
