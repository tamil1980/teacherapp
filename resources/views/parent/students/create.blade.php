@extends('layouts.app')

@section('title', 'Add Child Profile')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div>
            <h1 class="page-title">Add child profile</h1>
            <p class="page-subtitle">Tell us about your child so we can match the right teacher for their class and board.</p>
        </div>

        <form method="POST" action="{{ route('parent.students.store') }}" class="mt-6 space-y-6">
            @csrf

            <div class="form-card">
                <h2 class="text-base font-semibold text-slate-900">Basic details</h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="display_name" class="label">Display name <span class="text-rose-500">*</span></label>
                        <input id="display_name" name="display_name" type="text" value="{{ old('display_name') }}" required
                               maxlength="120" class="input" placeholder="e.g. Aarav">
                        <p class="hint">This name is shown to teachers during bookings.</p>
                        @error('display_name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="class_standard_id" class="label">Class</label>
                            <select id="class_standard_id" name="class_standard_id" class="input">
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('class_standard_id') === (int) $class->id)>
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
                                    <option value="{{ $board->id }}" @selected((int) old('board_id') === (int) $board->id)>
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
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                            @error('gender') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="date_of_birth" class="label">Date of birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date"
                                   value="{{ old('date_of_birth') }}"
                                   max="{{ now()->subDay()->format('Y-m-d') }}" class="input">
                            <p class="hint">Optional. Used only to recommend age-appropriate teaching material.</p>
                            @error('date_of_birth') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="school_name" class="label">School</label>
                        <input id="school_name" name="school_name" type="text" value="{{ old('school_name') }}"
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
                                  class="input" placeholder="e.g. Struggles with trigonometry; needs extra practice on chapter exercises.">{{ old('learning_requirements') }}</textarea>
                        <p class="hint">Shared with teachers you book so the class can be tailored.</p>
                        @error('learning_requirements') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <fieldset>
                        <legend class="label">Subject interests</legend>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($subjects as $subject)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <input type="checkbox" name="subject_interests[]" value="{{ $subject->id }}"
                                           @checked(in_array($subject->id, old('subject_interests', []) ? array_map('intval', old('subject_interests', [])) : [], true))
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
                <button type="submit" class="btn-primary">Save child profile</button>
                <a href="{{ route('parent.students.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
