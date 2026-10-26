@extends('layouts.app')

@section('title', 'Receipt ' . $payment->reference_no)

@section('content')
    <style>
        @media print {
            header,
            footer,
            nav,
            .print-hide {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            .receipt-sheet {
                box-shadow: none !important;
                border: 0 !important;
                padding: 0 !important;
            }

            .receipt-sheet table {
                font-size: 12px;
            }

            @page {
                margin: 14mm;
            }
        }
    </style>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print-hide">
            <h1 class="page-title">Payment receipt</h1>
            <div class="flex flex-wrap gap-3">
                <button type="button" onclick="window.print()" class="btn-primary">Print / Save as PDF</button>
                <a href="{{ route('parent.payments.show', $payment) }}" class="btn-secondary">Payment details</a>
                <a href="{{ route('parent.payments.index') }}" class="btn-ghost">← All payments</a>
            </div>
        </div>

        <div class="receipt-sheet card p-8">
            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-lg font-black text-white">E</span>
                        <span class="text-xl font-extrabold tracking-tight text-slate-900">{{ $platformName ?? 'EduBook' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">Tuition payment receipt</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Receipt number</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $receipt['reference_no'] ?? $payment->reference_no }}</p>
                    <p class="mt-2 text-xs text-slate-500">Date: {{ $receipt['date'] ?? '—' }}</p>
                </div>
            </div>

            {{-- Meta --}}
            <dl class="grid gap-4 border-b border-slate-200 py-6 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Invoice number</dt>
                    <dd class="mt-1 font-mono text-sm text-slate-900">{{ $receipt['invoice_number'] ?? $payment->invoice_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booking reference</dt>
                    <dd class="mt-1 font-mono text-sm text-slate-900">{{ $receipt['booking_reference'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment method</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ isset($receipt['method']) && $receipt['method'] ? ucfirst(str_replace('_', ' ', $receipt['method'])) : '—' }}
                    </dd>
                </div>
            </dl>

            {{-- Line items --}}
            <div class="py-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Charges</h2>

                @if (! empty($receipt['lines']))
                    <table class="mt-4 w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="pb-2 font-semibold text-slate-700">Description</th>
                                <th class="pb-2 text-right font-semibold text-slate-700">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($receipt['lines'] as $line)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2.5 text-slate-700">{{ $line['label'] }}</td>
                                    <td class="py-2.5 text-right font-medium text-slate-900">
                                        ₹{{ number_format((float) $line['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="mt-3 text-sm text-slate-500">No itemised charges are available for this payment.</p>
                @endif
            </div>

            {{-- Totals --}}
            <div class="flex justify-end border-t border-slate-200 pt-6">
                <dl class="w-full max-w-xs space-y-2 text-sm">
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-slate-600">Tax</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) ($receipt['tax'] ?? 0), 2) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-t border-slate-200 pt-3">
                        <dt class="font-semibold text-slate-900">Total paid</dt>
                        <dd class="text-lg font-bold text-slate-900">
                            ₹{{ number_format((float) ($receipt['amount'] ?? $payment->amount), 2) }}
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-slate-600">Currency</dt>
                        <dd class="font-medium text-slate-900">{{ $receipt['currency'] ?? $payment->currency ?? 'INR' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4">
                        <dt class="text-slate-600">Status</dt>
                        <dd class="font-medium text-slate-900">{{ ucfirst(str_replace('_', ' ', (string) ($receipt['status'] ?? $payment->status))) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Footer --}}
            <div class="mt-8 border-t border-slate-200 pt-4 text-center text-xs text-slate-400">
                <p>
                    {{ $platformName ?? 'EduBook' }} · Amounts in Indian Rupees (INR) · Times shown in Asia/Kolkata.
                </p>
                <p class="mt-1">This is a computer generated receipt and does not require a signature.</p>
            </div>
        </div>
    </div>
@endsection
