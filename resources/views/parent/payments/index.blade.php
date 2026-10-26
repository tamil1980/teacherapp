@extends('layouts.app')

@section('title', 'Payments & Refunds')

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
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Payments &amp; refunds</h1>
                <p class="page-subtitle">Every payment made against your children's bookings.</p>
            </div>
            <a href="{{ route('parent.bookings.index') }}" class="btn-secondary">View bookings</a>
        </div>

        {{-- Totals --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total paid</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₹{{ number_format((float) $totals['paid'], 2) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total refunded</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₹{{ number_format((float) $totals['refunded'], 2) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Refund pending</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₹{{ number_format((float) $totals['refund_pending'], 2) }}</p>
            </div>
        </div>

        {{-- Payments --}}
        <div class="card mt-8">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Payments</h2>
                <span class="text-xs text-slate-500">{{ $payments->total() }} total</span>
            </div>

            @if ($payments->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    No payments have been made yet.
                </div>
            @else
                <div class="table-wrap rounded-none border-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Invoice</th>
                                <th>Booking</th>
                                <th>Teacher</th>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th class="text-right">Refunded</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="font-mono text-xs text-slate-600">{{ $payment->reference_no }}</td>
                                    <td class="font-mono text-xs text-slate-600">{{ $payment->invoice_number ?? '—' }}</td>
                                    <td class="font-mono text-xs text-slate-600">
                                        @if ($payment->booking)
                                            <a href="{{ route('parent.bookings.show', $payment->booking) }}" class="hover:text-brand-600 hover:underline">
                                                {{ $payment->booking->reference_no }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="font-medium text-slate-900">
                                        {{ $payment->booking?->teacher?->user?->name ?? '—' }}
                                        @if ($payment->booking?->subject)
                                            <p class="text-xs font-normal text-slate-500">{{ $payment->booking->subject->name }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-slate-600">
                                        {{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="whitespace-nowrap text-right font-medium text-slate-900">
                                        ₹{{ number_format((float) $payment->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusClasses[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $statusLabels[$payment->status] ?? ucfirst(str_replace('_', ' ', (string) $payment->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-slate-600">{{ $payment->method ? ucfirst(str_replace('_', ' ', $payment->method)) : '—' }}</td>
                                    <td class="whitespace-nowrap text-right text-slate-600">
                                        ₹{{ number_format((float) $payment->refunded_amount, 2) }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('parent.payments.show', $payment) }}" class="btn btn-sm btn-secondary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

        {{-- Refunds --}}
        <div class="card mt-8">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Refunds</h2>
                <span class="text-xs text-slate-500">{{ $refunds->total() }} total</span>
            </div>

            @if ($refunds->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    No refunds have been raised.
                </div>
            @else
                <div class="table-wrap rounded-none border-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Payment</th>
                                <th class="text-right">Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Processed on</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($refunds as $refund)
                                <tr>
                                    <td class="font-mono text-xs text-slate-600">{{ $refund->reference_no }}</td>
                                    <td class="font-mono text-xs text-slate-600">
                                        @if ($refund->payment)
                                            <a href="{{ route('parent.payments.show', $refund->payment) }}" class="hover:text-brand-600 hover:underline">
                                                {{ $refund->payment->reference_no }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right font-medium text-slate-900">
                                        ₹{{ number_format((float) $refund->amount, 2) }}
                                    </td>
                                    <td class="text-slate-600">{{ $refund->reason ?? '—' }}</td>
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

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $refunds->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
