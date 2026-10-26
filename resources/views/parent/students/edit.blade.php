@extends('layouts.app')

@section('title', 'Edit Child Profile')

@section('content')
    @php
        $selectedSubjects = array_map('intval', (array) old('subject_interests', $student->subject_interests ?? []));
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div>
            <h1 class="page-title">Edit child profile</h1>
            <p class="page-subtitle">Update the details for {{ $student->display_name }}.</p>
        </div>

        <form method="POST" action="{{ route('parent.students.update', $student) }}" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="form-card">
                <h2 class="text-base font-semibold text-slate-900">Basic details</h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="display_name" class="label">Display name <span class="text-rose-500">*</span></label>
                        <input id="display_name" name="display_name" type="text" value="{{ old('display_name', $student->display_name) }}" required
                               maxlength="120" class="input">
                        <p class="hint">This name is shown to teachers during bookings.</p>
                        @error('display_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="class_standard_id" class="label">Class</label>
                            <select id="class_standard_id" name="class_standard_id" class="input">
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('class_standard_id', $student->class_standard_id) === (int) $class->id)>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_standard_id') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="board_id" class="label">Board</label>
                            <select id="board_id" name="board_id" class="input">
                                <option value="">Select board</option>
                                @foreach ($boards as $board)
                                    <option value="{{ $board->id }}" @selected((int) old('board_id', $student->board_id) === (int) $board->id)>
                                        {{ $board->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('board_id') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="gender" class="label">Gender</label>
                            <select id="gender" name="gender" class="input">
                                <option value="">Select gender</option>
                                <option value="male" @selected(old('gender', $student->gender) === 'male')>Male</option>
                                <option value="female" @selected(old('gender', $student->gender) === 'female')>Female</option>
                                <option value="other" @selected(old('gender', $student->gender) === 'other')>Other</option>
                            </select>
                            @error('gender') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="label">Date of birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date"
                                   value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}"
                                   max="{{ now()->subDay()->format('Y-m-d') }}" class="input">
                            @error('date_of_birth') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="school_name" class="label">School</label>
                        <input id="school_name" name="school_name" type="text" value="{{ old('school_name', $student->school_name) }}"
                               maxlength="180" class="input" placeholder="e.g. Delhi Public School">
                        @error('school_name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h2 class="text-base font-semibold text-slate-900">Learning needs</h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="learning_requirements" class="label">Learning requirements</label>
                        <textarea id="learning_requirements" name="learning_requirements" rows="4" maxlength="2000"
                                  class="input">{{ old('learning_requirements', $student->learning_requirements) }}</textarea>
                        <p class="hint">Shared with teachers you book so the class can be tailored.</p>
                        @error('learning_requirements') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <fieldset>
                        <legend class="label">Subject interests</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($subjects as $subject)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <input type="checkbox" name="subject_interests[]" value="{{ $subject->id }}"
                                           @checked(in_array((int) $subject->id, $selectedSubjects, true))
                                           class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    {{ $subject->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('subject_interests') <p class="error">{{ $message }}</p> @enderror
                        @error('subject_interests.*') <p class="error">{{ $message }}</p> @enderror
                    </fieldset>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary">Save changes</button>
                <a href="{{ route('parent.students.show', $student) }}" class="btn-secondary">Cancel</a>
                <a href="{{ route('parent.students.index') }}" class="btn-ghost">Back to all children</a>
            </div>
        </form>
    </div>
@endsection
