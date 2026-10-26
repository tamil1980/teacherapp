@extends('layouts.app')

@section('title', 'Boards — EduBook')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">School boards</h1>
                <p class="page-subtitle">CBSE, State Board, ICSE and Matriculation boards available for tuition.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="btn-secondary">Browse teachers</a>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($boards as $board)
                <a href="{{ route('boards.show', $board) }}" class="card block p-6 transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-3xl">🏫</span>
                        @if ($board->is_active)
                            <span class="badge bg-emerald-100 text-emerald-800">Active</span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $board->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ Str::limit($board->description ?? '', 110) }}</p>
                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-brand-600">{{ strtoupper($board->code) }}</p>
                </a>
            @empty
                <div class="card p-12 text-center">
                    <div class="text-4xl">🏫</div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No boards are available yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Please check back later or contact support.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
