@extends('layouts.app')

@section('title', 'Class standards — EduBook')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Class standards</h1>
                <p class="page-subtitle">The classes and stages tutors teach, from early primary through Class 12.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="btn-secondary">Browse teachers</a>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($classes as $class)
                <a href="{{ route('classes.show', $class) }}" class="card block p-6 transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-3xl">🎓</span>
                        @if ($class->is_active)
                            <span class="badge bg-emerald-100 text-emerald-800">Active</span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $class->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $class->stage ?: 'General' }} @if ($class->level_order) · Level order {{ $class->level_order }} @endif
                    </p>
                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-brand-600">{{ strtoupper($class->code) }}</p>
                </a>
            @empty
                <div class="card p-12 text-center">
                    <div class="text-4xl">🎓</div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No class standards are available yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Please check back later or contact support.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
