@extends('layouts.app')

@section('title', 'Find teachers')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Find a teacher</h1>
                <p class="page-subtitle">
                    {{ $teachers->total() }} {{ Str::plural('teacher', $teachers->total()) }} available
                    @if (isset($filters['subject_id']) && $filters['subject_id'])
                        for {{ optional($options['subjects']->firstWhere('id', $filters['subject_id']))->name ?? 'the selected subject' }}
                    @endif
                    — every hourly fee below is set by the teacher.
                </p>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[280px_1fr]">
            {{-- Filters --}}
            <aside>
                <form method="GET" action="{{ route('teachers.index') }}"
                      class="card sticky top-20 space-y-4 p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900">Filters</h2>
                        <a href="{{ route('teachers.index') }}" class="text-xs font-semibold text-brand-600 hover:underline">Reset</a>
                    </div>

                    <div>
                        <label for="q" class="label">Search</label>
                        <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                               placeholder="Teacher name or keyword" class="input">
                    </div>

                    <div>
                        <label for="subject_id" class="label">Subject</label>
                        <select id="subject_id" name="subject_id" class="input">
                            <option value="">Any subject</option>
                            @foreach ($options['subjects'] as $subject)
                                <option value="{{ $subject->id }}" @selected(($filters['subject_id'] ?? null) == $subject->id)>
                                    {{ $subject->icon }} {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="board_id" class="label">School board</label>
                        <select id="board_id" name="board_id" class="input">
                            <option value="">Any board</option>
                            @foreach ($options['boards'] as $board)
                                <option value="{{ $board->id }}" @selected(($filters['board_id'] ?? null) == $board->id)>
                                    {{ $board->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_standard_id" class="label">Class</label>
                        <select id="class_standard_id" name="class_standard_id" class="input">
                            <option value="">Any class</option>
                            @foreach ($options['classes'] as $class)
                                <option value="{{ $class->id }}" @selected(($filters['class_standard_id'] ?? null) == $class->id)>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset>
                        <legend class="label">Teaching mode</legend>
                        <div class="space-y-2">
                            @foreach ($options['modes'] as $mode)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="mode" value="{{ $mode['value'] }}"
                                           class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500"
                                           @checked(($filters['mode'] ?? null) === $mode['value'])>
                                    {{ $mode['label'] }}
                                </label>
                            @endforeach
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="mode" value="" class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500"
                                       @checked(empty($filters['mode']))>
                                Any mode
                            </label>
                        </div>
                    </fieldset>

                    <div>
                        <label for="city" class="label">City</label>
                        <input id="city" name="city" type="text" value="{{ $filters['city'] ?? '' }}" class="input">
                    </div>

                    <div>
                        <label for="min_fee" class="label">Hourly fee range (₹)</label>
                        <div class="flex items-center gap-2">
                            <input id="min_fee" name="min_fee" type="number" min="0" step="50"
                                   value="{{ $filters['min_fee'] ?? '' }}" placeholder="{{ (int) $priceRange['min'] }}" class="input">
                            <span class="text-slate-400">—</span>
                            <input name="max_fee" type="number" min="0" step="50"
                                   value="{{ $filters['max_fee'] ?? '' }}" placeholder="{{ (int) $priceRange['max'] }}" class="input">
                        </div>
                        <p class="hint">Matching teachers charge between ₹{{ number_format($priceRange['min'], 0) }} and ₹{{ number_format($priceRange['max'], 0) }} per hour.</p>
                    </div>

                    <div>
                        <label for="min_rating" class="label">Minimum rating</label>
                        <select id="min_rating" name="min_rating" class="input">
                            <option value="">Any rating</option>
                            @foreach ([4.5, 4, 3.5, 3] as $rating)
                                <option value="{{ $rating }}" @selected(isset($filters['min_rating']) && (float) $filters['min_rating'] === (float) $rating)>
                                    {{ number_format($rating, 1) }} ★ and above
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="experience" class="label">Minimum experience (years)</label>
                        <input id="experience" name="experience" type="number" min="0" max="50"
                               value="{{ $filters['experience'] ?? '' }}" class="input">
                    </div>

                    <button type="submit" class="btn-primary w-full">Apply filters</button>
                </form>
            </aside>

            {{-- Results --}}
            <section>
                @if ($teachers->isEmpty())
                    <div class="card p-12 text-center">
                        <div class="text-4xl">🔍</div>
                        <h2 class="mt-4 text-lg font-bold text-slate-900">No teachers match these filters</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                            Try widening the hourly fee range, clearing the class or board filter, or searching for a different subject.
                        </p>
                        <a href="{{ route('teachers.index') }}" class="btn-secondary mt-6">Clear all filters</a>
                    </div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($teachers as $teacher)
                            @include('partials.teacher-card', [
                                'teacher' => $teacher,
                                'lowestRate' => $teacher->fees->where('is_active', true)->min('hourly_rate'),
                            ])
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $teachers->links() }}</div>
                @endif
            </section>
        </div>
    </div>
@endsection
