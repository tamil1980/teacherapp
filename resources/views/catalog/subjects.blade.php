@extends('layouts.app')

@section('title', 'Subjects — EduBook')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Subjects</h1>
                <p class="page-subtitle">Tutoring across CBSE, State Board, Matriculation and ICSE curricula, plus competitive exam prep.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="btn-secondary">Browse teachers</a>
        </div>

        @if ($subjects->isEmpty())
            <div class="card mt-8 p-12 text-center">
                <div class="text-4xl">📚</div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">No subjects are available yet</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Please check back later or contact support.</p>
            </div>
        @else
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($subjects as $subject)
                    <a href="{{ route('subjects.show', $subject) }}" class="card block p-6 transition hover:shadow-md">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-3xl">{{ $subject->icon }}</span>
                            <span class="badge bg-slate-100 text-slate-600">{{ $subject->teacher_subjects_count ?? 0 }} teachers</span>
                        </div>
                        <h2 class="mt-3 text-lg font-bold text-slate-900">{{ $subject->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ Str::limit($subject->description ?? '', 110) }}</p>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-brand-600">{{ strtoupper($subject->code) }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
