@extends('layouts.app')

@section('title', 'Booking confirmed')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8 text-center">
    <div class="card p-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="mt-4 page-title">Booking Confirmed!</h1>
        <p class="mt-2 text-slate-600">Your booking has been confirmed successfully</p>
        
        <div class="mt-6 rounded-lg bg-slate-50 p-4 text-left">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-600">Reference</dt>
                    <dd class="font-mono font-medium text-slate-900">{{ $booking->reference_no }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-600">Teacher</dt>
                    <dd class="text-slate-900">{{ $booking->teacher->user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-600">Date & Time</dt>
                    <dd class="text-slate-900">{{ $booking->start_at->format('d M Y, h:i A') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-600">Amount</dt>
                    <dd class="font-medium text-slate-900">₹{{ number_format($booking->total_amount, 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('parent.bookings.show', $booking) }}" class="btn-primary">View Booking</a>
            <a href="{{ route('parent.bookings.index') }}" class="btn-secondary">My Bookings</a>
        </div>
    </div>
</div>
@endsection
