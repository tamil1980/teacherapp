<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $parent = $this->parentProfile();

        $payments = Payment::where('parent_profile_id', $parent->id)
            ->with(['booking.teacher.user', 'booking.subject'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $refunds = \App\Models\Refund::whereIn('payment_id', $parent->payments()->select('payments.id'))
            ->latest()
            ->paginate(10);

        $totals = [
            'paid' => (float) Payment::where('parent_profile_id', $parent->id)->where('status', Payment::STATUS_PAID)->sum('amount'),
            'refunded' => (float) Payment::where('parent_profile_id', $parent->id)->sum('refunded_amount'),
            'refund_pending' => (float) \App\Models\Refund::whereIn('payment_id', $parent->payments()->select('payments.id'))->where('status', 'pending')->sum('amount'),
        ];

        return view('parent.payments.index', compact('payments', 'refunds', 'totals'));
    }

    public function show(Payment $payment): View
    {
        $this->authorizePayment($payment);

        $payment->load(['booking.teacher.user', 'booking.subject', 'refunds']);

        return view('parent.payments.show', compact('payment'));
    }

    public function receipt(Payment $payment): View
    {
        $this->authorizePayment($payment);

        $receipt = app(\App\Services\PaymentService::class)->receipt($payment);

        return view('parent.payments.receipt', compact('payment', 'receipt'));
    }

    private function authorizePayment(Payment $payment): void
    {
        abort_unless(
            $payment->parent_profile_id === $this->parentProfile()?->id,
            403,
            'You are not allowed to view this payment.'
        );
    }
}
