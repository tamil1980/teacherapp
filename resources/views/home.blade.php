@extends('layouts.app')

@section('title', 'Find verified teachers for tuition')

@section('content')
    <section class="home-hero">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
            <div class="grid items-center gap-8 lg:grid-cols-[1fr_1.05fr] lg:gap-12">
                <div>
                    <span class="home-eyebrow">
                        <span class="h-2 w-2 rounded-full bg-emerald-300" aria-hidden="true"></span>
                        Verified teachers · Flexible learning
                    </span>
                    <h1 class="mt-5 max-w-2xl text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-[3.5rem]">
                        Find the Right Teacher for Your Child
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-relaxed text-indigo-100 sm:text-lg">
                        Discover verified teachers for State Board, Matriculation, CBSE and ICSE. Choose online classes or offline tuition near you.
                    </p>
                    <a href="{{ route('teachers.index') }}" class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-white underline decoration-white/40 underline-offset-4 hover:decoration-white">
                        Browse all teachers <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="home-search-panel">
                    <div class="mb-5">
                        <p class="text-lg font-bold text-slate-900">Start your search</p>
                        <p class="mt-1 text-sm text-slate-500">Choose what matters to your family. You can refine results later.</p>
                    </div>
                    <form action="{{ route('teachers.index') }}" method="GET" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="hero-subject" class="label">Subject</label>
                            <select id="hero-subject" name="subject_id" class="input">
                                <option value="">Any subject</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->icon }} {{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="hero-board" class="label">School board</label>
                            <select id="hero-board" name="board_id" class="input">
                                <option value="">Any board</option>
                                @foreach ($boards as $board)
                                    <option value="{{ $board->id }}">{{ $board->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="hero-class" class="label">Class</label>
                            <select id="hero-class" name="class_standard_id" class="input">
                                <option value="">Any class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="hero-mode" class="label">Teaching mode</label>
                            <select id="hero-mode" name="mode" class="input">
                                <option value="">Online or offline</option>
                                <option value="online">Online classes</option>
                                <option value="offline">Offline tuition</option>
                            </select>
                        </div>
                        <div>
                            <label for="hero-city" class="label">City or town</label>
                            <input id="hero-city" name="city" type="text" class="input" placeholder="e.g. Chennai">
                        </div>
                        <div>
                            <label for="hero-pincode" class="label">PIN code <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="hero-pincode" name="pincode" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="10" class="input" placeholder="Enter PIN code">
                        </div>
                        <button type="submit" class="btn-primary sm:col-span-2">
                            Find teachers <span aria-hidden="true">→</span>
                        </button>
                    </form>
                    <p class="mt-4 text-xs leading-relaxed text-slate-500">Compare profiles, teaching options and hourly fees before choosing a teacher.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-7 sm:gap-6 sm:px-6 lg:grid-cols-5 lg:px-8">
            @foreach ([
                ['label' => 'Verified teachers', 'value' => number_format($stats['teachers']), 'icon' => '✓'],
                ['label' => 'Active subjects', 'value' => number_format($stats['subjects']), 'icon' => '◈'],
                ['label' => 'School boards', 'value' => number_format($stats['boards']), 'icon' => '▤'],
                ['label' => 'Class standards', 'value' => number_format($stats['classes']), 'icon' => '⌂'],
                ['label' => 'Published reviews', 'value' => number_format($stats['reviews']), 'icon' => '☆'],
            ] as $stat)
                <div class="flex items-center justify-center gap-3 py-2 text-center lg:justify-start lg:text-left">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg font-bold text-brand-700" aria-hidden="true">{{ $stat['icon'] }}</span>
                    <div>
                        <div class="text-xl font-extrabold tracking-tight text-slate-900">{{ $stat['value'] }}</div>
                        <div class="text-xs font-medium text-slate-500">{{ $stat['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Subjects --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Browse by subject</h2>
                <p class="mt-1 text-sm text-slate-500">Pick a subject to see teachers who teach it.</p>
            </div>
            <a href="{{ route('subjects.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">All subjects →</a>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($subjects as $subject)
                <a href="{{ route('subjects.show', $subject->slug) }}"
                   class="card flex items-center gap-3 p-4 transition hover:border-brand-300 hover:shadow-md">
                    <span class="text-2xl">{{ $subject->icon }}</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $subject->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Boards --}}
    <section class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Teachers by school board</h2>
                    <p class="mt-1 text-sm text-slate-500">Every board listed below is configurable by our administrators.</p>
                </div>
                <a href="{{ route('boards.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">All boards →</a>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($boards as $board)
                    <a href="{{ route('boards.show', $board->slug) }}" class="card p-5 transition hover:border-brand-300 hover:shadow-md">
                        <h3 class="font-bold text-slate-900">{{ $board->name }}</h3>
                        <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $board->description }}</p>
                        <span class="mt-3 inline-flex items-center text-xs font-semibold text-brand-600">Find teachers →</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Featured teachers --}}
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Top verified teachers</h2>
                <p class="mt-1 text-sm text-slate-500">Only teachers whose identity and qualifications have been verified appear here.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Browse all teachers →</a>
        </div>

        @if ($featured->isEmpty())
            <div class="card mt-6 p-10 text-center text-slate-500">
                No verified teachers are available yet. Please check back soon.
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $teacher)
                    @include('partials.teacher-card', ['teacher' => $teacher, 'lowestRate' => $teacher->fees->where('is_active', true)->min('hourly_rate')])
                @endforeach
            </div>
        @endif
    </section>

    <section id="how-it-works" class="bg-white py-14 scroll-mt-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">A simple way to get started</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">How EduBook works</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Find a tutor whose experience, teaching mode and schedule fit your child's learning needs.</p>
            </div>
            <div class="mt-7 grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['number' => '01', 'title' => 'Explore teachers', 'copy' => 'Filter by subject, board, class, location and online or offline teaching.'],
                    ['number' => '02', 'title' => 'Compare profiles', 'copy' => 'Review each teacher’s qualifications, teaching experience, fees and available slots.'],
                    ['number' => '03', 'title' => 'Choose a class', 'copy' => 'Select a suitable teacher and follow the booking steps shown on the platform.'],
                ] as $step)
                    <article class="card p-5 sm:p-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-sm font-bold text-brand-700">{{ $step['number'] }}</span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-sky-100 bg-sky-50/70 p-6 sm:p-8">
                <span class="badge bg-sky-100 text-sky-800">Learn from anywhere</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">Online classes</h2>
                <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-600">Browse teachers who offer online lessons and find a schedule that works for your family.</p>
                <a href="{{ route('teachers.index', ['mode' => 'online']) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:underline">
                    Find online teachers <span aria-hidden="true">→</span>
                </a>
            </article>
            <article class="rounded-2xl border border-teal-100 bg-teal-50/70 p-6 sm:p-8">
                <span class="badge bg-teal-100 text-teal-800">Tuition near you</span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">Offline classes</h2>
                <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-600">Search for teachers offering offline tuition and narrow results by your city or PIN code.</p>
                <a href="{{ route('teachers.index', ['mode' => 'offline']) }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:underline">
                    Find offline teachers <span aria-hidden="true">→</span>
                </a>
            </article>
        </div>
    </section>

    {{-- Class standards --}}
    <section class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Teachers by class standard</h2>
            <p class="mt-1 text-sm text-slate-500">From early primary through Class 12 and competitive exam preparation.</p>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($classes as $class)
                    <a href="{{ route('classes.show', $class->code) }}"
                       class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">
                        {{ $class->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Good to know</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Frequently asked questions</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Find answers about choosing a teacher and booking a class.</p>
                <a href="{{ route('faq') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:underline">Visit the help centre <span aria-hidden="true">→</span></a>
            </div>
            <div class="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white px-5">
                <details class="group py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        How do I find a teacher for my child?
                        <span class="text-brand-700 transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Search by subject, board, class, teaching mode and location, then open a profile to review the teacher's details.</p>
                </details>
                <details class="group py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Can I see a teacher's fees before booking?
                        <span class="text-brand-700 transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Teacher profiles and search results show available hourly fees when the teacher has published pricing.</p>
                </details>
                <details class="group py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                        Are online and offline lessons available?
                        <span class="text-brand-700 transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Teaching options depend on each teacher. Use the teaching-mode filter to see teachers who offer online or offline classes.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section id="become-a-teacher" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 scroll-mt-24">
        <div class="grid gap-6 rounded-2xl bg-slate-900 p-8 lg:grid-cols-2 lg:p-12">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-white">Are you a teacher?</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-300">
                    Set your own hourly fees for every subject, class and teaching mode. Publish your availability,
                    take bookings online or offline, and get paid after each completed session minus only the platform commission.
                </p>
                <a href="{{ route('register.teacher') }}" class="btn-primary mt-6">Register as a teacher</a>
            </div>
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-white">Looking for a tutor?</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-300">
                    Create a free parent account, add your child's class and board, and browse verified teachers who
                    are free in your area. Pay only after you confirm the time slot.
                </p>
                <a href="{{ route('register.parent') }}" class="btn mt-6 bg-white text-slate-900 hover:bg-slate-100">Create a parent account</a>
            </div>
        </div>
    </section>
@endsection
