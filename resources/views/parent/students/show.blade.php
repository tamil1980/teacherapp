@extends('layouts.app')

@section('title', $student->display_name)

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
        ];

        $modeClasses = [
            'online' => 'bg-violet-100 text-violet-700',
            'offline' => 'bg-teal-100 text-teal-700',
        ];

        $teacherQuery = array_filter([
            'class_standard_id' => $student->class_standard_id,
            'board_id' => $student->board_id,
        ], fn ($value) => $value !== null);
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $student->display_name }}</h1>
                <p class="page-subtitle">
                    {{ $student->classStandard?->name ?? 'Class not set' }}
                    @if ($student->board)
                        <span class="text-slate-400">·</span> {{ $student->board->name }}
                    @endif
                    @if ($student->school_name)
                        <span class="text-slate-400">·</span> {{ $student->school_name }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('teachers.index', $teacherQuery) }}" class="btn-primary">Book a class</a>
                <a href="{{ route('parent.students.edit', $student) }}" class="btn-secondary">Edit profile</a>
                <a href="{{ route('parent.students.index') }}" class="btn-ghost">All children</a>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-1">
                <h2 class="text-base font-semibold text-slate-900">Profile</h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $student->classStandard?->name ?? 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Board</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $student->board?->name ?? 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">School</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $student->school_name ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Learning requirements</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-800">
                            {{ $student->learning_requirements ?: 'No specific requirements noted.' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="lg:col-span-2">
                <div class="card">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Bookings</h2>
                        <span class="text-xs text-slate-500">{{ $bookings->total() }} total</span>
                    </div>

                    @if ($bookings->isEmpty())
                        <div class="px-5 py-12 text-center">
                            <p class="text-sm font-medium text-slate-700">No bookings for {{ $student->display_name }} yet</p>
                            <p class="mt-1 text-sm text-slate-500">Find a verified teacher to book the first class.</p>
                            <a href="{{ route('teachers.index', $teacherQuery) }}" class="btn-primary btn-sm mt-4">Find a teacher</a>
                        </div>
                    @else
                        <div class="table-wrap rounded-none border-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Teacher</th>
                                        <th>Date &amp; time</th>
                                        <th>Mode</th>
                                        <th>Status</th>
                                        <th class="text-right">Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bookings as $booking)
                                        <tr>
                                            <td class="font-mono text-xs text-slate-600">{{ $booking->reference_no }}</td>
                                            <td>
                                                <p class="font-medium text-slate-900">{{ $booking->teacher?->user?->name ?? '—' }}</p>
                                                <p class="text-xs text-slate-500">{{ $booking->subject?->name ?? '—' }}</p>
                                            </td>
                                            <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                                            <td>
                                                <span class="badge {{ $modeClasses[$booking->mode] ?? 'bg-slate-100 text-slate-600' }}">
                                                    {{ $booking->mode === 'online' ? 'Online' : 'Offline' }}
                                                </span>
                                            </td>
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

                        <div class="border-t border-slate-100 px-5 py-4">
                            {{ $bookings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
