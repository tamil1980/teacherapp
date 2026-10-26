@extends('layouts.app')

@section('title', 'Cancellation policy — EduBook')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="page-title">Cancellation policy</h1>
            <p class="page-subtitle">Refunds, rescheduling and slot handling</p>
        </div>

        <div class="mt-10 space-y-8">
            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Cancellation window</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Parents may cancel a confirmed booking within
                    <strong>{{ $cancellationWindow }} hours</strong> of the scheduled start time.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Cancellation fee</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Cancellations made inside the window are refunded at
                    <strong>{{ (float) $cancellationFeePercent }}%</strong> of the paid amount,
                    or as defined by the policy text below.
                </p>
                @if ($policyText)
                    <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                        {{ $policyText }}
                    </div>
                @endif
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Rescheduling</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Bookings may be moved to another slot within
                    <strong>{{ $rescheduleWindow }} hours</strong> of the scheduled start, provided the new slot is still available.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Teacher cancellations</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    When a teacher cancels or cannot attend, the slot is released and the parent is refunded in full plus any applicable adjustment. The teacher may be required to cover the refund as part of quality monitoring.
                </p>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Settlement</h2>
                <p class="mt-2 text-sm leading-relaxed">
                    Teacher earnings are settled after
                    <strong>{{ $settlementDelayDays }} days</strong> for completed sessions, following the platform payout schedule.
                </p>
            </section>
        </div>
    </div>
@endsection
