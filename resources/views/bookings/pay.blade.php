@extends('layouts.app')

@section('title', 'Payment')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="page-title">Complete payment</h1>
    <p class="page-subtitle">Booking {{ $booking->reference_no }}</p>

    <div class="mt-6 card p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600">Amount to pay</p>
                <p class="text-3xl font-bold text-slate-900">₹{{ number_format($payment->amount, 2) }}</p>
            </div>
            @if($isMockGateway)
                <span class="badge bg-amber-100 text-amber-700">Test Mode</span>
            @endif
        </div>

        <div class="mt-6">
            @if($isMockGateway)
                <form method="POST" action="{{ route('bookings.pay.process', $booking) }}">
                    @csrf
                    <input type="hidden" name="razorpay_order_id" value="{{ $payment->gateway_order_id }}">
                    <input type="hidden" name="razorpay_payment_id" value="pay_test_{{ Str::random(10) }}">
                    <input type="hidden" name="razorpay_signature" value="{{ hash_hmac('sha256', $payment->gateway_order_id.'|pay_test_'.Str::random(10), config('services.razorpay.key_secret') ?? 'test') }}">
                    <button type="submit" class="btn-primary w-full">Pay ₹{{ number_format($payment->amount, 2) }} (Test Payment)</button>
                </form>
                <p class="mt-2 text-xs text-center text-slate-500">This is a mock gateway for testing</p>
            @else
                <p class="text-sm text-slate-600">Payment gateway integration</p>
            @endif
        </div>
    </div>
</div>
@endsection
