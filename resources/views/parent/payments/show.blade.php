@extends('layouts.app')

@section('title', 'Payment ' . $payment->reference_no)

@section('content')
    @php
        $statusClasses = [
            'created' => 'bg-slate-100 text-slate-600',
            'paid' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-rose-100 text-rose-700',
            'refunded' => 'bg-slate-100 text-slate-600',
            'partially_refunded' => 'bg-sky-100 text-sky-700',
        ];

        $statusLabels = [
            'created' => 'Created',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'partially_refunded' => 'Partly refunded',
        ];

        $refundClasses = [
            'pending' => 'bg-amber-100 text-amber-700',
            'processing' => 'bg-sky-100 text-sky-700',
            'processed' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-rose-100 text-rose-700',
        ];

        $refundLabels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'processed' => 'Processed',
            'failed' => 'Failed',
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</p>
                <h1 class="page-title font-mono">{{ $payment->reference_no }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="badge {{ $statusClasses[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $statusLabels[$payment->status] ?? ucfirst(str_replace('_', ' ', (string) $payment->status)) }}
                    </span>
                    @if ($payment->method)
                        <span class="badge bg-slate-100 text-slate-600">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                    @endif
                    <span class="badge bg-slate-100 text-slate-600">{{ $payment->gateway ?: 'unknown gateway' }}</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('parent.payments.receipt', $payment) }}" class="btn-primary">View / print receipt</a>
                <a href="{{ route('parent.payments.index') }}" class="btn-secondary">← All payments</a>
            </div>
        </div>

        @if ($payment->status === 'failed' && $payment->failure_reason)
            <div class="alert-error mt-6">
                <p class="font-semibold">This payment did not go through.</p>
                <p class="mt-1">{{ $payment->failure_reason }}</p>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Amounts --}}
            <div class="card p-5">
                <h2 class="text-base font-semibold text-slate-900">Amounts</h2>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Amount paid</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) $payment->amount, 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Tax</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) $payment->tax_amount, 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Gateway fee</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) $payment->gateway_fee, 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Refunded</dt>
                        <dd class="font-medium text-emerald-700">₹{{ number_format((float) $payment->refunded_amount, 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Refundable balance</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) $payment->refundableAmount(), 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3 border-t border-slate-200 pt-3">
                        <dt class="text-slate-600">Currency</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->currency ?: 'INR' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Gateway / meta --}}
            <div class="card p-5">
                <h2 class="text-base font-semibold text-slate-900">Transaction details</h2>

                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Invoice number</dt>
                        <dd class="font-mono text-xs text-slate-900">{{ $payment->invoice_number ?? '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Gateway</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->gateway ?: '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Order ID</dt>
                        <dd class="font-mono text-xs text-slate-900">{{ $payment->order_id ?? '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Gateway order ID</dt>
                        <dd class="font-mono text-xs text-slate-900">{{ $payment->gateway_order_id ?? '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Gateway payment ID</dt>
                        <dd class="font-mono text-xs text-slate-900">{{ $payment->gateway_payment_id ?? '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Method</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->method ? ucfirst(str_replace('_', ' ', $payment->method)) : '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Paid at</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->paid_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-3">
                        <dt class="text-slate-600">Created at</dt>
                        <dd class="font-medium text-slate-900">{{ $payment->created_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Linked booking --}}
        <div class="card mt-6 p-5">
            <h2 class="text-base font-semibold text-slate-900">Linked booking</h2>

            @if ($payment->booking)
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference</dt>
                        <dd class="mt-1">
                            <a href="{{ route('parent.bookings.show', $payment->booking) }}" class="font-mono text-sm text-brand-600 hover:underline">
                                {{ $payment->booking->reference_no }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $payment->booking->teacher?->user?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $payment->booking->subject?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class date</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $payment->booking->start_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                    </div>
                </dl>
            @else
                <p class="mt-4 text-sm text-slate-500">This payment is not linked to a booking.</p>
            @endif
        </div>

        {{-- Refund history --}}
        <div class="card mt-6 p-5">
            <h2 class="text-base font-semibold text-slate-900">Refund history</h2>

            @if ($payment->refunds->isEmpty())
                <p class="mt-4 text-sm text-slate-500">No refunds have been raised against this payment.</p>
            @else
                <div class="table-wrap mt-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th class="text-right">Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Processed on</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payment->refunds as $refund)
                                <tr>
                                    <td class="font-mono text-xs text-slate-600">{{ $refund->reference_no }}</td>
                                    <td class="whitespace-nowrap text-right font-medium text-slate-900">
                                        ₹{{ number_format((float) $refund->amount, 2) }}
                                    </td>
                                    <td class="text-slate-600">
                                        {{ $refund->reason ?? '—' }}
                                        @if ($refund->notes)
                                            <p class="text-xs text-slate-500">{{ $refund->notes }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $refundClasses[$refund->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $refundLabels[$refund->status] ?? ucfirst(str_replace('_', ' ', (string) $refund->status)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap text-slate-600">
                                        {{ $refund->processed_at?->format('d M Y, h:i A') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
