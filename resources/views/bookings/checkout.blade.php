@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="page-title">Checkout</h1>
    <p class="page-subtitle">Review your booking details before payment</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Booking details</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $booking->teacher->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Student</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $booking->student->display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date & Time</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $booking->start_at->format('d M Y, h:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Duration</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $booking->duration_minutes }} minutes</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ ucfirst($booking->mode) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $booking->subject?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Cancellation policy</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $cancellationPolicy }}</p>
                <p class="mt-2 text-xs text-slate-500">Free cancellation within {{ $cancellationWindow }} hours before class. Late cancellation may incur {{ $cancellationFeePercent }}% fee.</p>
            </div>
        </div>

        <div>
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Price breakdown</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Rate ({{ $quote['duration_minutes']/60 }} hrs)</dt>
                        <dd class="text-slate-900">₹{{ number_format($quote['subtotal'], 2) }}</dd>
                    </div>
                    @if($quote['travel_charge'] > 0)
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Travel charge</dt>
                        <dd class="text-slate-900">₹{{ number_format($quote['travel_charge'], 2) }}</dd>
                    </div>
                    @endif
                    @if($quote['discount_amount'] > 0)
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Discount</dt>
                        <dd class="text-emerald-700">-₹{{ number_format($quote['discount_amount'], 2) }}</dd>
                    </div>
                    @endif
                    @if($quote['tax_amount'] > 0)
                    <div class="flex justify-between">
                        <dt class="text-slate-600">Tax</dt>
                        <dd class="text-slate-900">₹{{ number_format($quote['tax_amount'], 2) }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-3">
                        <dt class="font-semibold text-slate-900">Total</dt>
                        <dd class="text-lg font-bold text-slate-900">₹{{ number_format($quote['total_amount'], 2) }}</dd>
                    </div>
                </dl>
                <a href="{{ route('bookings.pay', $booking) }}" class="btn-primary mt-6 w-full">Proceed to payment</a>
            </div>
        </div>
    </div>
</div>
@endsection
