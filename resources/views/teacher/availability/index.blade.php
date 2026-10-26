@extends('layouts.app')

@section('title', 'Availability')

@section('content')
    @php
        $monthStart = \Illuminate\Support\Carbon::createFromFormat('Y-m', $calendar['month'])->startOfMonth();
        $leadingDays = $monthStart->dayOfWeek;
        $today = now()->toDateString();
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-brand-700">Teacher workspace</p>
                <h1 class="page-title mt-1">Availability</h1>
                <p class="page-subtitle">Set your regular weekly teaching hours and add dates when your schedule changes.</p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="btn-secondary">Back to dashboard</a>
        </div>

        <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="card p-4 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Availability calendar</h2>
                        <p class="mt-1 text-sm text-slate-500">Available slot counts are based on your published schedule.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('teacher.availability.index', ['month' => $calendar['previous_month']]) }}"
                           class="btn-secondary btn-sm" aria-label="Previous month">←</a>
                        <span class="min-w-28 text-center text-sm font-semibold text-slate-800">{{ $calendar['label'] }}</span>
                        <a href="{{ route('teacher.availability.index', ['month' => $calendar['next_month']]) }}"
                           class="btn-secondary btn-sm" aria-label="Next month">→</a>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-7 gap-1.5 sm:gap-2" role="grid" aria-label="{{ $calendar['label'] }}">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                        <div class="py-2 text-center text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs" role="columnheader">{{ $weekday }}</div>
                    @endforeach

                    @for ($blank = 0; $blank < $leadingDays; $blank++)
                        <div aria-hidden="true"></div>
                    @endfor

                    @foreach ($calendar['days'] as $day)
                        @php
                            $isPast = $day['date'] < $today;
                            $isAvailable = $day['available_slots'] > 0;
                            $dayClasses = $isAvailable
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                : 'border-slate-200 bg-slate-50 text-slate-500';
                            if ($day['is_today']) {
                                $dayClasses .= ' ring-2 ring-brand-400 ring-offset-1';
                            }
                        @endphp
                        <div role="gridcell" aria-label="{{ \Illuminate\Support\Carbon::parse($day['date'])->format('l, j F') }}: {{ $day['available_slots'] }} available slots"
                             class="min-h-16 rounded-lg border p-1.5 text-center sm:min-h-20 sm:p-2 {{ $dayClasses }}">
                            <span class="text-xs font-semibold sm:text-sm">{{ $day['day'] }}</span>
                            <span class="mt-1 block text-[0.6rem] leading-tight sm:text-[0.7rem]">
                                @if ($isAvailable)
                                    {{ $day['available_slots'] }} available
                                @elseif ($day['total_slots'] > 0)
                                    No open slots
                                @else
                                    —
                                @endif
                            </span>
                            @if ($day['counts']['booked'] > 0)
                                <span class="mt-0.5 block text-[0.6rem] text-slate-500 sm:text-[0.7rem]">{{ $day['counts']['booked'] }} booked</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border border-emerald-200 bg-emerald-50"></span>Has availability</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border border-slate-200 bg-slate-50"></span>No open slots</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm ring-2 ring-brand-400"></span>Today</span>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-slate-500">Dates with no published hours or one-off slots will not appear as available to parents.</p>
            </section>

            <div class="space-y-6">
                <section class="form-card">
                    <h2 class="text-lg font-bold text-slate-900">Weekly hours</h2>
                    <p class="mt-1 text-sm text-slate-500">Add a recurring time window. Existing reservations remain protected.</p>

                    <form method="POST" action="{{ route('teacher.availability.rules.store') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="weekday" class="label">Day of week</label>
                            <select id="weekday" name="weekday" class="input" required>
                                @foreach ($weekdays as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('weekday') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('weekday') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="start_time" class="label">Start time</label>
                                <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" class="input" required>
                                @error('start_time') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="end_time" class="label">End time</label>
                                <input id="end_time" name="end_time" type="time" value="{{ old('end_time') }}" class="input" required>
                                @error('end_time') <p class="error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="mode" class="label">Teaching mode</label>
                            <select id="mode" name="mode" class="input" required>
                                @foreach ($modes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mode', 'both') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('mode') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="session_minutes" class="label">Session length</label>
                                <select id="session_minutes" name="session_minutes" class="input" required>
                                    @foreach ([30, 45, 60, 90, 120, 180] as $minutes)
                                        <option value="{{ $minutes }}" @selected((int) old('session_minutes', 60) === $minutes)>{{ $minutes }} min</option>
                                    @endforeach
                                </select>
                                @error('session_minutes') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="buffer_minutes" class="label">Buffer (min)</label>
                                <input id="buffer_minutes" name="buffer_minutes" type="number" min="0" max="120" step="5"
                                       value="{{ old('buffer_minutes', 0) }}" class="input">
                                @error('buffer_minutes') <p class="error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="effective_from" class="label">Starts on <span class="font-normal text-slate-400">(optional)</span></label>
                                <input id="effective_from" name="effective_from" type="date" value="{{ old('effective_from') }}" class="input">
                                @error('effective_from') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="effective_to" class="label">Ends on <span class="font-normal text-slate-400">(optional)</span></label>
                                <input id="effective_to" name="effective_to" type="date" value="{{ old('effective_to') }}" class="input">
                                @error('effective_to') <p class="error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full">Add weekly hours</button>
                    </form>
                </section>

                <section class="form-card">
                    <h2 class="text-lg font-bold text-slate-900">Add a date-specific slot</h2>
                    <p class="mt-1 text-sm text-slate-500">Publish extra availability, block a period, or mark a holiday.</p>

                    <form method="POST" action="{{ route('teacher.availability.slots.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label for="slot_date" class="label">Date</label>
                            <input id="slot_date" name="slot_date" type="date" min="{{ $today }}" value="{{ old('slot_date') }}" class="input" required>
                            @error('slot_date') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="slot_start_time" class="label">Start time</label>
                                <input id="slot_start_time" name="start_time" type="time" value="{{ old('start_time') }}" class="input" required>
                                @error('start_time') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="slot_end_time" class="label">End time</label>
                                <input id="slot_end_time" name="end_time" type="time" value="{{ old('end_time') }}" class="input" required>
                                @error('end_time') <p class="error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label for="slot_status" class="label">Schedule status</label>
                            <select id="slot_status" name="status" class="input" required>
                                @foreach ($slotStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'available') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="slot_mode" class="label">Teaching mode</label>
                            <select id="slot_mode" name="mode" class="input" required>
                                @foreach ($modes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('mode', 'both') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('mode') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="note" class="label">Note <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="note" name="note" type="text" maxlength="255" value="{{ old('note') }}" class="input" placeholder="e.g. Unavailable this afternoon">
                            @error('note') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-secondary w-full">Add date-specific slot</button>
                    </form>
                </section>
            </div>
        </div>

        <section class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Your weekly schedule</h2>
            </div>
            @if ($rules->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-500">No weekly availability yet. Add regular hours above to let parents find times that work.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($rules as $rule)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $rule->weekdayName() }} · {{ \Illuminate\Support\Carbon::parse($rule->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($rule->end_time)->format('g:i A') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $modes[$rule->mode] ?? ucfirst($rule->mode) }} · {{ $rule->session_minutes }} min sessions@if ($rule->buffer_minutes), {{ $rule->buffer_minutes }} min buffer@endif</p>
                                @if ($rule->effective_from || $rule->effective_to)
                                    <p class="mt-1 text-xs text-slate-500">
                                        Effective {{ $rule->effective_from?->format('d M Y') ?? 'any date' }}–{{ $rule->effective_to?->format('d M Y') ?? 'ongoing' }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <details class="group relative">
                                    <summary class="btn-secondary btn-sm cursor-pointer list-none">Edit hours</summary>
                                    <form method="POST" action="{{ route('teacher.availability.rules.update', $rule) }}" class="absolute right-0 z-20 mt-2 w-[min(22rem,calc(100vw-3rem))] space-y-3 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-xl">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label for="rule-weekday-{{ $rule->id }}" class="label">Day of week</label>
                                            <select id="rule-weekday-{{ $rule->id }}" name="weekday" class="input" required>
                                                @foreach ($weekdays as $value => $label)
                                                    <option value="{{ $value }}" @selected($rule->weekday === (int) $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label for="rule-start-{{ $rule->id }}" class="label">Start time</label>
                                                <input id="rule-start-{{ $rule->id }}" name="start_time" type="time" value="{{ \Illuminate\Support\Carbon::parse($rule->start_time)->format('H:i') }}" class="input" required>
                                            </div>
                                            <div>
                                                <label for="rule-end-{{ $rule->id }}" class="label">End time</label>
                                                <input id="rule-end-{{ $rule->id }}" name="end_time" type="time" value="{{ \Illuminate\Support\Carbon::parse($rule->end_time)->format('H:i') }}" class="input" required>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="rule-mode-{{ $rule->id }}" class="label">Teaching mode</label>
                                            <select id="rule-mode-{{ $rule->id }}" name="mode" class="input" required>
                                                @foreach ($modes as $value => $label)
                                                    <option value="{{ $value }}" @selected($rule->mode === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label for="rule-session-{{ $rule->id }}" class="label">Session length</label>
                                                <input id="rule-session-{{ $rule->id }}" name="session_minutes" type="number" min="15" max="480" value="{{ $rule->session_minutes }}" class="input" required>
                                            </div>
                                            <div>
                                                <label for="rule-buffer-{{ $rule->id }}" class="label">Buffer (min)</label>
                                                <input id="rule-buffer-{{ $rule->id }}" name="buffer_minutes" type="number" min="0" max="120" value="{{ $rule->buffer_minutes }}" class="input">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label for="rule-from-{{ $rule->id }}" class="label">Starts on</label>
                                                <input id="rule-from-{{ $rule->id }}" name="effective_from" type="date" value="{{ $rule->effective_from?->format('Y-m-d') }}" class="input">
                                            </div>
                                            <div>
                                                <label for="rule-to-{{ $rule->id }}" class="label">Ends on</label>
                                                <input id="rule-to-{{ $rule->id }}" name="effective_to" type="date" value="{{ $rule->effective_to?->format('Y-m-d') }}" class="input">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn-primary btn-sm w-full">Save schedule changes</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('teacher.availability.rules.destroy', $rule) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">Remove</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Upcoming date-specific slots</h2>
            </div>
            @if ($slots->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-500">No upcoming date-specific slots.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($slots as $slot)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $slot->slot_date->format('D, d M Y') }} · {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('g:i A') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $slotStatuses[$slot->status] ?? ucfirst($slot->status) }} · {{ $modes[$slot->mode] ?? ucfirst($slot->mode) }}@if ($slot->note) · {{ $slot->note }}@endif</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <details class="group relative">
                                    <summary class="btn-secondary btn-sm cursor-pointer list-none">Edit slot</summary>
                                    <form method="POST" action="{{ route('teacher.availability.slots.update', $slot) }}" class="absolute right-0 z-20 mt-2 w-[min(22rem,calc(100vw-3rem))] space-y-3 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-xl">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label for="slot-date-{{ $slot->id }}" class="label">Date</label>
                                            <input id="slot-date-{{ $slot->id }}" name="slot_date" type="date" min="{{ $today }}" value="{{ $slot->slot_date->format('Y-m-d') }}" class="input" required>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label for="slot-start-{{ $slot->id }}" class="label">Start time</label>
                                                <input id="slot-start-{{ $slot->id }}" name="start_time" type="time" value="{{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('H:i') }}" class="input" required>
                                            </div>
                                            <div>
                                                <label for="slot-end-{{ $slot->id }}" class="label">End time</label>
                                                <input id="slot-end-{{ $slot->id }}" name="end_time" type="time" value="{{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('H:i') }}" class="input" required>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="slot-status-{{ $slot->id }}" class="label">Schedule status</label>
                                            <select id="slot-status-{{ $slot->id }}" name="status" class="input" required>
                                                @foreach ($slotStatuses as $value => $label)
                                                    <option value="{{ $value }}" @selected($slot->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="slot-mode-{{ $slot->id }}" class="label">Teaching mode</label>
                                            <select id="slot-mode-{{ $slot->id }}" name="mode" class="input" required>
                                                @foreach ($modes as $value => $label)
                                                    <option value="{{ $value }}" @selected($slot->mode === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="slot-note-{{ $slot->id }}" class="label">Note</label>
                                            <input id="slot-note-{{ $slot->id }}" name="note" type="text" maxlength="255" value="{{ $slot->note }}" class="input">
                                        </div>
                                        <button type="submit" class="btn-primary btn-sm w-full">Save slot changes</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('teacher.availability.slots.destroy', $slot) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm">Remove</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
