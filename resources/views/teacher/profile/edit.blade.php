@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">My profile</h1>
            <p class="page-subtitle">This is what parents see when they browse teachers on {{ $platformName ?? 'EduBook' }}.</p>
        </div>
        <span class="badge {{ $teacher->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
            Verification: {{ ucfirst($teacher->verification_status) }}
        </span>
    </div>

    <div class="mt-6 alert-info">
        Verification status is decided by the {{ $platformName ?? 'EduBook' }} administrator and cannot be changed from this page.
    </div>

    {{-- Photo --}}
    <section class="form-card mt-6">
        <h2 class="text-lg font-semibold text-slate-900">Profile photo</h2>

        <div class="mt-4 flex flex-wrap items-center gap-6">
            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-2xl font-bold text-brand-700">
                @if ($teacher->photo_path)
                    <img src="{{ Storage::disk('public')->url($teacher->photo_path) }}"
                         alt="Your profile photo" class="h-full w-full object-cover">
                @else
                    {{ strtoupper(substr($teacher->user->name, 0, 1)) }}
                @endif
            </div>

            <form method="POST" action="{{ route('teacher.profile.photo') }}" enctype="multipart/form-data" class="flex-1">
                @csrf
                <label class="label" for="photo">Upload a new photo</label>
                <input type="file" name="photo" id="photo" accept="image/*" class="input @error('photo') border-rose-300 @enderror" required>
                <p class="hint">JPG, PNG or WEBP. Maximum 3&nbsp;MB.</p>
                @error('photo')
                    <p class="error">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn-primary btn-sm mt-3">Upload photo</button>
            </form>
        </div>
    </section>

    {{-- Profile details --}}
    <section class="form-card mt-6">
        <h2 class="text-lg font-semibold text-slate-900">Profile details</h2>

        <form method="POST" action="{{ route('teacher.profile.update') }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="label" for="headline">Headline</label>
                <input type="text" name="headline" id="headline" maxlength="255"
                       value="{{ old('headline', $teacher->headline) }}"
                       class="input @error('headline') border-rose-300 @enderror"
                       placeholder="e.g. Physics &amp; Mathematics Faculty — 12 years in CBSE">
                @error('headline')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="bio">Short bio</label>
                <textarea name="bio" id="bio" rows="4" maxlength="5000"
                          class="input @error('bio') border-rose-300 @enderror">{{ old('bio', $teacher->bio) }}</textarea>
                @error('bio')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="teaching_description">What you teach</label>
                <textarea name="teaching_description" id="teaching_description" rows="3" maxlength="5000"
                          class="input @error('teaching_description') border-rose-300 @enderror">{{ old('teaching_description', $teacher->teaching_description) }}</textarea>
                @error('teaching_description')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="methodology">Teaching methodology</label>
                <textarea name="methodology" id="methodology" rows="3" maxlength="5000"
                          class="input @error('methodology') border-rose-300 @enderror">{{ old('methodology', $teacher->methodology) }}</textarea>
                @error('methodology')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="experience_years">Years of experience</label>
                    <input type="number" name="experience_years" id="experience_years" min="0" max="60"
                           value="{{ old('experience_years', $teacher->experience_years) }}"
                           class="input @error('experience_years') border-rose-300 @enderror">
                    @error('experience_years')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="qualification_summary">Qualification summary</label>
                    <input type="text" name="qualification_summary" id="qualification_summary" maxlength="255"
                           value="{{ old('qualification_summary', $teacher->qualification_summary) }}"
                           class="input @error('qualification_summary') border-rose-300 @enderror"
                           placeholder="e.g. M.Sc. Physics, B.Ed.">
                    @error('qualification_summary')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            <fieldset>
                <legend class="label">Languages you teach in</legend>
                <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($languages as $language)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="languages[]" value="{{ $language }}"
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                   @checked(in_array($language, (array) old('languages', $teacher->languages ?? [])))>
                            {{ $language }}
                        </label>
                    @endforeach
                </div>
                @error('languages')<p class="error">{{ $message }}</p>@enderror
                @error('languages.*')<p class="error">{{ $message }}</p>@enderror
            </fieldset>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Base address &amp; travel</h3>

                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label" for="address_line1">Address</label>
                        <input type="text" name="address_line1" id="address_line1" maxlength="255"
                               value="{{ old('address_line1', $teacher->address_line1) }}"
                               class="input @error('address_line1') border-rose-300 @enderror">
                        @error('address_line1')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="city">City</label>
                        <input type="text" name="city" id="city" maxlength="100"
                               value="{{ old('city', $teacher->city) }}"
                               class="input @error('city') border-rose-300 @enderror">
                        @error('city')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="state">State</label>
                        <input type="text" name="state" id="state" maxlength="100"
                               value="{{ old('state', $teacher->state) }}"
                               class="input @error('state') border-rose-300 @enderror">
                        @error('state')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="pincode">PIN code</label>
                        <input type="text" name="pincode" id="pincode" maxlength="10"
                               value="{{ old('pincode', $teacher->pincode) }}"
                               class="input @error('pincode') border-rose-300 @enderror">
                        @error('pincode')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="service_radius_km">Service radius (km)</label>
                        <input type="number" step="0.5" min="0.5" max="100" name="service_radius_km" id="service_radius_km"
                               value="{{ old('service_radius_km', $teacher->service_radius_km) }}"
                               class="input @error('service_radius_km') border-rose-300 @enderror">
                        @error('service_radius_km')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="latitude">Latitude</label>
                        <input type="number" step="0.0000001" name="latitude" id="latitude"
                               value="{{ old('latitude', $teacher->latitude) }}"
                               class="input @error('latitude') border-rose-300 @enderror">
                        @error('latitude')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="longitude">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" id="longitude"
                               value="{{ old('longitude', $teacher->longitude) }}"
                               class="input @error('longitude') border-rose-300 @enderror">
                        @error('longitude')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <label class="label" for="cancellation_policy">Cancellation policy</label>
                <textarea name="cancellation_policy" id="cancellation_policy" rows="3" maxlength="255"
                          class="input @error('cancellation_policy') border-rose-300 @enderror">{{ old('cancellation_policy', $teacher->cancellation_policy) }}</textarea>
                <p class="hint">Shown to parents before they book. Maximum 255 characters.</p>
                @error('cancellation_policy')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" class="btn-primary">Save profile</button>
            </div>
        </form>
    </section>

    {{-- Teaching preferences --}}
    <section class="form-card mt-6">
        <h2 class="text-lg font-semibold text-slate-900">What you teach</h2>
        <p class="page-subtitle">These lists decide which parents can find and book you.</p>

        <div class="mt-4 space-y-6">

            {{-- Subjects --}}
            <form method="POST" action="{{ route('teacher.profile.subjects') }}">
                @csrf
                @method('PUT')
                <label class="label">Subjects</label>
                <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($subjects as $subject)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="subjects[]" value="{{ $subject->id }}"
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                   @checked(in_array($subject->id, (array) old('subjects', $teacher->teacherSubjects->pluck('subject_id')->all())))>
                            {{ $subject->name }}
                        </label>
                    @endforeach
                </div>
                @error('subjects')<p class="error">{{ $message }}</p>@enderror
                @error('subjects.*')<p class="error">{{ $message }}</p>@enderror
                <div class="mt-3">
                    <button type="submit" class="btn-secondary btn-sm">Save subjects</button>
                </div>
            </form>

            <hr class="border-slate-100">

            {{-- Boards --}}
            <form method="POST" action="{{ route('teacher.profile.boards') }}">
                @csrf
                @method('PUT')
                <label class="label">School boards</label>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($boards as $board)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="boards[]" value="{{ $board->id }}"
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                   @checked(in_array($board->id, (array) old('boards', $teacher->teacherBoards->pluck('board_id')->all())))>
                            {{ $board->name }}
                        </label>
                    @endforeach
                </div>
                @error('boards')<p class="error">{{ $message }}</p>@enderror
                @error('boards.*')<p class="error">{{ $message }}</p>@enderror
                <div class="mt-3">
                    <button type="submit" class="btn-secondary btn-sm">Save boards</button>
                </div>
            </form>

            <hr class="border-slate-100">

            {{-- Classes --}}
            <form method="POST" action="{{ route('teacher.profile.classes') }}">
                @csrf
                @method('PUT')
                <label class="label">Class standards</label>
                <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($classStandards as $classStandard)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="classes[]" value="{{ $classStandard->id }}"
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                   @checked(in_array($classStandard->id, (array) old('classes', $teacher->teacherClassStandards->pluck('class_standard_id')->all())))>
                            {{ $classStandard->name }}
                        </label>
                    @endforeach
                </div>
                @error('classes')<p class="error">{{ $message }}</p>@enderror
                @error('classes.*')<p class="error">{{ $message }}</p>@enderror
                <div class="mt-3">
                    <button type="submit" class="btn-secondary btn-sm">Save classes</button>
                </div>
            </form>

            <hr class="border-slate-100">

            {{-- Modes --}}
            <form method="POST" action="{{ route('teacher.profile.modes') }}">
                @csrf
                @method('PUT')
                <label class="label">Teaching modes</label>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (['online' => 'Online classes', 'offline' => 'Offline (at-home) classes'] as $mode => $modeLabel)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" name="modes[]" value="{{ $mode }}"
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                   @checked(in_array($mode, (array) old('modes', $teacher->teachingModes->pluck('mode')->all())))>
                            {{ $modeLabel }}
                        </label>
                    @endforeach
                </div>
                @error('modes')<p class="error">{{ $message }}</p>@enderror
                @error('modes.*')<p class="error">{{ $message }}</p>@enderror
                <div class="mt-3">
                    <button type="submit" class="btn-secondary btn-sm">Save teaching modes</button>
                </div>
            </form>
        </div>
    </section>

    {{-- Qualifications --}}
    <section class="form-card mt-6">
        <h2 class="text-lg font-semibold text-slate-900">Qualifications</h2>
        <p class="page-subtitle">Each qualification is reviewed by the administrator before it is marked verified.</p>

        @if ($teacher->qualifications->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No qualifications added yet.</p>
        @else
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Degree</th>
                            <th>Institution</th>
                            <th>Year</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teacher->qualifications as $qualification)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $qualification->degree }}</td>
                                <td>
                                    {{ $qualification->institution ?: '—' }}
                                    @if ($qualification->specialisation)
                                        <span class="block text-xs text-slate-500">{{ $qualification->specialisation }}</span>
                                    @endif
                                </td>
                                <td>{{ $qualification->year_of_passing ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $qualification->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($qualification->verification_status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('teacher.profile.qualifications.destroy', $qualification) }}"
                                          onsubmit="return confirm('Remove this qualification?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('teacher.profile.qualifications.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4 border-t border-slate-100 pt-6">
            @csrf
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Add a qualification</h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="degree">Degree / certification</label>
                    <input type="text" name="degree" id="degree" maxlength="255" value="{{ old('degree') }}"
                           class="input @error('degree') border-rose-300 @enderror" required>
                    @error('degree')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="institution">Institution</label>
                    <input type="text" name="institution" id="institution" maxlength="255" value="{{ old('institution') }}"
                           class="input @error('institution') border-rose-300 @enderror">
                    @error('institution')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="board_university">Board / University</label>
                    <input type="text" name="board_university" id="board_university" maxlength="255" value="{{ old('board_university') }}"
                           class="input @error('board_university') border-rose-300 @enderror">
                    @error('board_university')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="year_of_passing">Year of passing</label>
                    <input type="number" name="year_of_passing" id="year_of_passing" min="1900" max="{{ now()->year }}"
                           value="{{ old('year_of_passing') }}"
                           class="input @error('year_of_passing') border-rose-300 @enderror">
                    @error('year_of_passing')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="specialisation">Specialisation</label>
                    <input type="text" name="specialisation" id="specialisation" maxlength="255" value="{{ old('specialisation') }}"
                           class="input @error('specialisation') border-rose-300 @enderror">
                    @error('specialisation')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="qualification_description">Description</label>
                    <textarea name="description" id="qualification_description" rows="3" maxlength="2000"
                              class="input @error('description') border-rose-300 @enderror">{{ old('description') }}</textarea>
                    @error('description')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="qualification_document">Scanned copy (optional)</label>
                    <input type="file" name="document" id="qualification_document" accept=".pdf,.jpg,.jpeg,.png"
                           class="input @error('document') border-rose-300 @enderror">
                    <p class="hint">PDF, JPG or PNG. Maximum 5&nbsp;MB.</p>
                    @error('document')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Add qualification</button>
            </div>
        </form>
    </section>

    {{-- Documents --}}
    <section class="form-card mt-6">
        <h2 class="text-lg font-semibold text-slate-900">Verification documents</h2>
        <p class="page-subtitle">Upload identity, address and qualification proof. Only the administrator can approve a document.</p>

        @if ($teacher->documents->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No documents uploaded yet.</p>
        @else
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>File</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teacher->documents as $document)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $document->title }}</td>
                                <td>{{ $documentTypes[$document->doc_type] ?? ucfirst(str_replace('_', ' ', $document->doc_type)) }}</td>
                                <td class="text-xs text-slate-500">{{ $document->original_name ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $document->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($document->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('teacher.profile.documents.destroy', $document) }}"
                                          onsubmit="return confirm('Remove this document?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('teacher.profile.documents.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4 border-t border-slate-100 pt-6">
            @csrf
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Upload a document</h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="doc_type">Document type</label>
                    <select name="doc_type" id="doc_type" class="input @error('doc_type') border-rose-300 @enderror" required>
                        @foreach ($documentTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('doc_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('doc_type')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="title">Title</label>
                    <input type="text" name="title" id="title" maxlength="255" value="{{ old('title') }}"
                           class="input @error('title') border-rose-300 @enderror" required>
                    @error('title')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="document_file">File</label>
                    <input type="file" name="file" id="document_file" accept=".pdf,.jpg,.jpeg,.png" required
                           class="input @error('file') border-rose-300 @enderror">
                    <p class="hint">PDF, JPG or PNG. Maximum 5&nbsp;MB.</p>
                    @error('file')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Upload document</button>
            </div>
        </form>
    </section>
</div>
@endsection
