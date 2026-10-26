@extends('layouts.app')

@section('title', 'Book a class')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Book with</p>
            <h1 class="page-title">{{ $teacher->user?->name }}</h1>
            <p class="page-subtitle">{{ $teacher->headline }}</p>
        </div>
        <a href="{{ route('teachers.show', $teacher) }}" class="btn-secondary">Back to profile</a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('bookings.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="teacher_profile_id" value="{{ $teacher->id }}">

                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">1. Select student</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($students as $student)
                            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4 cursor-pointer hover:border-brand-300">
                                <input type="radio" name="student_profile_id" value="{{ $student->id }}" class="mt-1" required {{ old('student_profile_id') == $student->id ? 'checked' : '' }}>
                                <div>
                                    <p class="font-medium text-slate-900">{{ $student->display_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $student->classStandard?->name }} · {{ $student->board?->name }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('student_profile_id') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">2. Class details</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Subject</label>
                            <select name="subject_id" class="input">
                                <option value="">Select subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Class</label>
                            <select name="class_standard_id" class="input">
                                <option value="">Select class</option>
                                @foreach($classStandards as $class)
                                    <option value="{{ $class->id }}" {{ old('class_standard_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Board</label>
                            <select name="board_id" class="input">
                                <option value="">Select board</option>
                                @foreach($boards as $board)
                                    <option value="{{ $board->id }}" {{ old('board_id') == $board->id ? 'selected' : '' }}>{{ $board->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Mode</label>
                            <select name="mode" class="input" required>
                                @if($teacher->accepts_online)<option value="online" {{ ($mode ?? 'online') == 'online' ? 'selected' : '' }}>Online</option>@endif
                                @if($teacher->accepts_offline)<option value="offline" {{ ($mode ?? '') == 'offline' ? 'selected' : '' }}>Offline</option>@endif
                            </select>
                        </div>
                    </div>
                </div>

                @if($locations->isNotEmpty())
                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">3. Location (for offline)</h2>
                    <div class="mt-4 space-y-2">
                        @foreach($locations as $location)
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                                <input type="radio" name="teacher_location_id" value="{{ $location->id }}" {{ old('teacher_location_id') == $location->id ? 'checked' : '' }}>
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $location->label }}</p>
                                    <p class="text-xs text-slate-500">{{ $location->city }} @if($location->travel_charge > 0) · Travel ₹{{ number_format($location->travel_charge, 0) }} @endif</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">4. Date, time & duration</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="label">Start time</label>
                            <input type="datetime-local" name="start_at" class="input" required value="{{ old('start_at') }}">
                        </div>
                        <div>
                            <label class="label">Duration (minutes)</label>
                            <input type="number" name="duration_minutes" class="input" min="15" max="600" step="15" required value="{{ old('duration_minutes', 60) }}">
                        </div>
                        <div>
                            <label class="label">Discount code (optional)</label>
                            <input type="text" name="discount_code" class="input" value="{{ old('discount_code') }}">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="label">Notes (optional)</label>
                        <textarea name="notes" rows="3" class="input">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Continue to checkout</button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Available slots</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $date->format('d M Y') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse($slots as $slot)
                        <span class="badge {{ $slot['status'] === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}"
    data-start-time="{{ $slot['start_time'] }}"
    data-duration="{{ $slot['duration_minutes'] }}"
    onclick="fillSlot(this)">
    {{ $slot['start_time'] }} - {{ $slot['end_time'] }} ({{ ucfirst($slot['status']) }})</span>
                    @empty
                        <p class="text-sm text-slate-500">No slots available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillSlot(element) {
    const startTime = element.dataset.startTime;
    const duration = element.dataset.duration;
    
    // Fill start_at datetime-local input
    const startInput = document.querySelector('input[name="start_at"]');
    if (startInput) {
        const dateInput = document.querySelector('input[name="date"]');
        const datePart = dateInput ? dateInput.value.split('T')[0] : new Date().toISOString().split('T')[0];
        startInput.value = datePart + 'T' + startTime;
    }
    
    // Fill duration_minutes input
    const durationInput = document.querySelector('input[name="duration_minutes"]');
    if (durationInput) {
        durationInput.value = duration;
    }
    
    // Auto-submit the form to proceed to checkout
    document.querySelector('button[type="submit"]').click();
}
</script>
