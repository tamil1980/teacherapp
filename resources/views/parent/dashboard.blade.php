@extends('layouts.app')

@section('title', 'Parent Dashboard')

@section('content')
    @php
        $statusClasses = [
            'pending_payment' => 'bg-amber-100 text-amber-700',
            'pending_teacher' => 'bg-amber-100 text-amber-700',
            'confirmed' => 'bg-emerald-100 text-emerald-700',
            'ongoing' => 'bg-brand-100 text-brand-700',
            'completed' => 'bg-sky-100 text-sky-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
            'refunded' => 'bg-slate-100 text-slate-600',
            'disputed' => 'bg-orange-100 text-orange-700',
            'unpaid' => 'bg-amber-100 text-amber-700',
            'paid' => 'bg-emerald-100 text-emerald-700',
            'partially_refunded' => 'bg-sky-100 text-sky-700',
        ];

        $statusLabels = [
            'pending_payment' => 'Payment pending',
            'pending_teacher' => 'Awaiting teacher',
            'confirmed' => 'Confirmed',
            'ongoing' => 'In progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'disputed' => 'Disputed',
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
            'partially_refunded' => 'Partly refunded',
        ];

        $modeClasses = [
            'online' => 'bg-violet-100 text-violet-700',
            'offline' => 'bg-teal-100 text-teal-700',
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Welcome header --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Welcome back, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}!</h1>
                <p class="page-subtitle">Manage your children's classes, teachers and payments in one place.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('parent.students.create') }}" class="btn-primary">+ Add child profile</a>
                <a href="{{ route('teachers.index') }}" class="btn-secondary">Find a teacher</a>
            </div>
        </div>

        {{-- KPI cards --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Children</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $students->count() }}</p>
                <a href="{{ route('parent.students.index') }}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:underline">Manage children</a>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming classes</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $upcoming->count() }}</p>
                <a href="{{ route('parent.bookings.index', ['upcoming' => 1]) }}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:underline">View upcoming</a>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completed sessions</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $completedCount }}</p>
                <a href="{{ route('parent.bookings.index', ['status' => 'completed']) }}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:underline">View history</a>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total spent</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₹{{ number_format((float) $totalSpent, 2) }}</p>
                <a href="{{ route('parent.payments.index') }}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:underline">Payment history</a>
            </div>
        </div>

        {{-- Pending reviews prompt --}}
        @if ($pendingReviews > 0)
            <div class="alert-warning mt-6 flex flex-wrap items-center justify-between gap-3">
                <p>
                    <span class="font-semibold">{{ $pendingReviews }} completed {{ \Illuminate\Support\Str::plural('session', $pendingReviews) }}</span>
                    {{ $pendingReviews === 1 ? 'is' : 'are' }} waiting for your feedback. Please rate your teacher to help other parents.
                </p>
                <a href="{{ route('parent.bookings.index', ['status' => 'completed']) }}" class="btn btn-sm border border-amber-300 bg-white text-amber-800 hover:bg-amber-100">Rate now</a>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Upcoming classes --}}
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Upcoming classes</h2>
                        <a href="{{ route('parent.bookings.index', ['upcoming' => 1]) }}" class="text-xs font-semibold text-brand-600 hover:underline">See all</a>
                    </div>

                    @if ($upcoming->isEmpty())
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-slate-700">No upcoming classes scheduled</p>
                            <p class="mt-1 text-sm text-slate-500">Book a verified teacher to get started.</p>
                            <a href="{{ route('teachers.index') }}" class="btn-primary btn-sm mt-4">Browse teachers</a>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($upcoming as $booking)
                                <li class="px-5 py-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">
                                                {{ $booking->teacher?->user?->name ?? 'Teacher' }}
                                                @if ($booking->subject)
                                                    <span class="font-normal text-slate-500">— {{ $booking->subject->name }}</span>
                                                @endif
                                            </p>
                                            <p class="mt-1 text-sm text-slate-600">
                                                {{ $booking->start_at?->format('d M Y, h:i A') }}
                                                @if ($booking->duration_minutes)
                                                    <span class="text-slate-400">· {{ $booking->duration_minutes }} min</span>
                                                @endif
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                Reference {{ $booking->reference_no }}
                                                @if ($booking->classStandard)
                                                    <span class="text-slate-400">· {{ $booking->classStandard->name }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <span class="badge {{ $modeClasses[$booking->mode] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $booking->mode === 'online' ? 'Online' : 'Offline' }}
                                            </span>
                                            <span class="badge {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                            </span>
                                            <a href="{{ route('parent.bookings.show', $booking) }}" class="btn btn-sm btn-secondary">View</a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Recent bookings --}}
                <div class="card mt-6">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Recent bookings</h2>
                        <a href="{{ route('parent.bookings.index') }}" class="text-xs font-semibold text-brand-600 hover:underline">See all</a>
                    </div>

                    @if ($recent->isEmpty())
                        <div class="px-5 py-10 text-center text-sm text-slate-500">No bookings yet.</div>
                    @else
                        <div class="table-wrap rounded-none border-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Teacher</th>
                                        <th>Date &amp; time</th>
                                        <th>Status</th>
                                        <th class="text-right">Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recent as $booking)
                                        <tr>
                                            <td class="font-mono text-xs text-slate-600">{{ $booking->reference_no }}</td>
                                            <td>
                                                <p class="font-medium text-slate-900">{{ $booking->teacher?->user?->name ?? '—' }}</p>
                                                <p class="text-xs text-slate-500">{{ $booking->subject?->name ?? '—' }}</p>
                                            </td>
                                            <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                                            <td>
                                                <span class="badge {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                                    {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap text-right font-medium text-slate-900">
                                                ₹{{ number_format((float) $booking->total_amount, 2) }}
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('parent.bookings.show', $booking) }}" class="btn btn-sm btn-secondary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Children sidebar --}}
            <div>
                <div class="card">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">My children</h2>
                        <a href="{{ route('parent.students.create') }}" class="text-xs font-semibold text-brand-600 hover:underline">+ Add</a>
                    </div>

                    @if ($students->isEmpty())
                        <div class="px-5 py-8 text-center">
                            <p class="text-sm text-slate-500">Add a child profile to start booking classes.</p>
                            <a href="{{ route('parent.students.create') }}" class="btn-primary btn-sm mt-4">Add child profile</a>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($students as $student)
                                <li class="px-5 py-4">
                                    <a href="{{ route('parent.students.show', $student) }}" class="block">
                                        <p class="text-sm font-semibold text-slate-900">{{ $student->display_name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ $student->classStandard?->name ?? 'Class not set' }}
                                            @if ($student->board)
                                                <span class="text-slate-400">· {{ $student->board->name }}</span>
                                            @endif
                                        </p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="card mt-6 p-5">
                    <h2 class="text-base font-semibold text-slate-900">Quick actions</h2>
                    <div class="mt-4 space-y-2">
                        <a href="{{ route('parent.bookings.index') }}" class="btn-secondary w-full">All my bookings</a>
                        <a href="{{ route('parent.payments.index') }}" class="btn-secondary w-full">Payments &amp; refunds</a>
                        <a href="{{ route('parent.reviews.index') }}" class="btn-secondary w-full">My reviews</a>
                        <a href="{{ route('support.create') }}" class="btn-secondary w-full">Get help</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
