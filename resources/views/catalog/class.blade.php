@extends('layouts.app')

@section('title', ($classStandard->name ?? 'Class') . ' — EduBook')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('classes.index') }}" class="text-brand-600 hover:underline">Class standards</a>
            <span>›</span>
            <span>{{ $classStandard->name }}</span>
        </div>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $classStandard->name }}</h1>
                <p class="page-subtitle">
                    @if ($classStandard->stage)
                        Stage: {{ $classStandard->stage }}
                    @endif
                    @if ($classStandard->level_order)
                        · Level {{ $classStandard->level_order }}
                    @endif
                </p>
            </div>
            <a href="{{ route('teachers.index') }}" class="btn-secondary">Browse teachers</a>
        </div>

        <div class="mt-8">
            @if ($teachers->isEmpty())
                <div class="card p-10 text-center text-slate-500">
                    <div class="text-4xl">🔍</div>
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No teachers found for this class yet</h2>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teachers as $teacher)
                        @include('partials.teacher-card', [
                            'teacher' => $teacher,
                            'lowestRate' => $teacher->fees->where('is_active', true)->min('hourly_rate'),
                        ])
                    @endforeach
                </div>
                <div class="mt-6">{{ $teachers->links() }}</div>
            @endif
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            <section>
                <h2 class="text-xl font-bold text-slate-900">Boards</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($boards as $board)
                        <span class="badge bg-slate-100 text-slate-700">{{ $board->name }}</span>
                    @endforeach
                </div>
            </section>

            <section>
                <h2 class="text-xl font-bold text-slate-900">Subjects</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($subjects as $subject)
                        <span class="badge bg-brand-50 text-brand-700">{{ $subject->icon }} {{ $subject->name }}</span>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
