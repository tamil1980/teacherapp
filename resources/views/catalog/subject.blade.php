@extends('layouts.app')

@section('title', ($subject->name ?? 'Subject') . ' — EduBook')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('subjects.index') }}" class="text-brand-600 hover:underline">{{ $subject->name }}</a>
            <span>›</span>
        </div>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $subject->icon }} {{ $subject->name }}</h1>
                <p class="page-subtitle">
                    @if ($subject->description)
                        {{ $subject->description }}
                    @endif
                </p>
            </div>
            <a href="{{ route('teachers.index', ['subject' => $subject->slug]) }}" class="btn-primary">Find teachers</a>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[280px_1fr]">
            <aside class="card h-fit p-5">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">On this page</h2>
                <nav class="mt-3 space-y-2 text-sm">
                    <a href="#teachers" class="block font-semibold text-slate-900 hover:text-brand-600">Teachers</a>
                    <a href="#boards" class="block text-slate-600 hover:text-brand-600">Boards</a>
                    <a href="#classes" class="block text-slate-600 hover:text-brand-600">Class standards</a>
                </nav>
            </aside>

            <div class="space-y-10">
                <section id="teachers">
                    <h2 class="text-xl font-bold text-slate-900">Teachers for {{ $subject->name }}</h2>
                    @if ($teachers->isEmpty())
                        <div class="card mt-4 p-10 text-center text-slate-500">No teachers are teaching this subject yet.</div>
                    @else
                        <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($teachers as $teacher)
                                @include('partials.teacher-card', [
                                    'teacher' => $teacher,
                                    'lowestRate' => $teacher->fees->where('is_active', true)->min('hourly_rate'),
                                ])
                            @endforeach
                        </div>
                        <div class="mt-6">{{ $teachers->links() }}</div>
                    @endif
                </section>

                <section id="boards">
                    <h2 class="text-xl font-bold text-slate-900">School boards</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($boards as $board)
                            <span class="badge bg-slate-100 text-slate-700">{{ $board->name }}</span>
                        @endforeach
                    </div>
                </section>

                <section id="classes">
                    <h2 class="text-xl font-bold text-slate-900">Class standards</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($classes as $class)
                            <span class="badge bg-slate-100 text-slate-700">{{ $class->name }}</span>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
