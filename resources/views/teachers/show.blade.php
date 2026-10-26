@extends('layouts.app')

@section('title', 'Teacher Profile')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher Profile</p>
            <h1 class="page-title">{{ $teacher->user?->name }}</h1>
            <p class="page-subtitle">{{ $teacher->headline }}</p>
        </div>
        @auth('web')
            @if (auth()->user()->role === 'parent')
                <a href="{{ route('bookings.create', ['teacher' => $teacher, 'mode' => $teacher->accepts_online ? 'online' : 'offline']) }}" class="btn-primary mt-5 w-full">
                    Book a class
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-primary mt-5 w-full">Log in to book</a>
            @endif
        @else
            <a href="{{ route('login') }}" class="btn-primary mt-5 w-full">Log in to book</a>
        @endauth
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            {{-- Teacher details --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">About This Teacher</h2>
                <p class="mt-4 text-slate-500">{{ $teacher->bio ?? 'No biography available' }}</p>
            </div>

            {{-- Fees --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Hourly Fees</h2>
                @foreach ($teacher->fees() as $fee)
                    @if ($fee->is_active)
                        <p class="mt-2">
                            <strong>{{ $fee->subject->name }} - {{ $fee->classStandard->name }} - {{ $fee->board->name }}:</strong> ₹{{ number_format($fee->hourly_rate, 0) }}/hour
                        </p>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            {{-- Available slots --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Available Slots</h2>
                <p class="mt-1 text-sm text-slate-500">Select a date to view availability</p>
                <div class="mt-4">
                    <!-- Date picker would go here -->
                    <p class="text-sm text-slate-500">No slots available for today</p>
                </div>
            </div>

            {{-- Teacher subjects --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Subjects Taught</h2>
                <p class="mt-1 text-sm text-slate-500">{{ count($teacher->teacherSubjects) }} subjects</p>
                @foreach ($teacher->teacherSubjects->take(4) as $teacherSubject)
                    <a href="{{ route('subjects.show', $teacherSubject->subject->slug) }}"
                       class="badge bg-brand-50 text-brand-700 hover:bg-brand-100 inline-block mb-1">
                        {{ $teacherSubject->subject->icon }} {{ $teacherSubject->subject->name }}
                    </a>
                @endforeach
                @if ($teacher->teacherSubjects->count() > 4)
                    <span class="badge bg-slate-100 text-slate-600">+{{ $teacher->teacherSubjects->count() - 4 }} more</span>
                @endif
            </div>

            {{-- Teacher boards --}}
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">School Boards</h2>
                <p class="mt-1 text-sm text-slate-500">{{ count($teacher->teacherBoards) }} boards</p>
                @foreach ($teacher->teacherBoards->take(3) as $teacherBoard)
                    <span class="badge bg-slate-100 text-slate-600 inline-block mb-1">{{ $teacherBoard->board->name }}</span>
                @endforeach
                @if ($teacher->teacherBoards->count() > 3)
                    <span class="badge bg-slate-100 text-slate-600">+{{ $teacher->teacherBoards->count() - 3 }} more</span>
                @endif
            </div>
        </div>
    </div>
</div>
@stop