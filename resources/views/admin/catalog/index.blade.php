@extends('layouts.app')

@section('title', 'Catalog — Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <div>
            <h1 class="page-title">Catalog</h1>
            <p class="page-subtitle">Boards, subjects and class standards used across the marketplace.</p>
        </div>

        {{-- Tabs --}}
        <div class="mt-6 flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1">
            @php
                $tabs = [
                    'boards' => 'Boards ('.$boards->count().')',
                    'subjects' => 'Subjects ('.$subjects->count().')',
                    'classes' => 'Class standards ('.$classes->count().')',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.catalog.index', ['tab' => $key]) }}"
                   class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $tab === $key ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($tab === 'boards')
            {{-- Create board --}}
            <form method="POST" action="{{ route('admin.catalog.boards.store') }}" class="card mt-6 p-6">
                <h2 class="text-lg font-semibold text-slate-900">Add a board</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="board-name" class="label">Name</label>
                        <input id="board-name" name="name" type="text" class="input" value="{{ old('name') }}" required>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="board-code" class="label">Code (stored in upper case)</label>
                        <input id="board-code" name="code" type="text" class="input uppercase" value="{{ old('code') }}" required placeholder="CBSE">
                        @error('code') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="board-slug" class="label">Slug (optional)</label>
                        <input id="board-slug" name="slug" type="text" class="input" value="{{ old('slug') }}" placeholder="cbse">
                        @error('slug') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="board-sort" class="label">Sort order</label>
                        <input id="board-sort" name="sort_order" type="number" min="0" class="input" value="{{ old('sort_order', 0) }}">
                        @error('sort_order') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                            Active
                        </label>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label for="board-description" class="label">Description</label>
                        <textarea id="board-description" name="description" rows="2" class="input">{{ old('description') }}</textarea>
                        @error('description') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-sm mt-4">Create board</button>
            </form>

            <div class="table-wrap mt-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Slug</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Used by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($boards as $board)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $board->name }}</td>
                                <td class="text-slate-600">{{ $board->code }}</td>
                                <td class="text-slate-600">{{ $board->slug }}</td>
                                <td class="text-slate-600">{{ $board->sort_order }}</td>
                                <td>
                                    <span class="badge {{ $board->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $board->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    @forelse ($usage['board'][$board->id] ?? [] as $reference)
                                        <span class="badge mr-1 bg-slate-100 text-slate-700">{{ $reference }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="text-right">
                                    <details class="inline-block text-left">
                                        <summary class="btn-secondary btn-sm cursor-pointer list-none">Edit</summary>
                                        <form method="POST" action="{{ route('admin.catalog.boards.update', $board) }}" class="mt-3 w-80 space-y-3 rounded-xl border border-slate-200 bg-white p-4">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="label">Name</label>
                                                <input name="name" type="text" class="input" value="{{ $board->name }}" required>
                                                @error('name') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Code</label>
                                                <input name="code" type="text" class="input uppercase" value="{{ $board->code }}" required>
                                                @error('code') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Slug</label>
                                                <input name="slug" type="text" class="input" value="{{ $board->slug }}">
                                                @error('slug') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Description</label>
                                                <textarea name="description" rows="2" class="input">{{ $board->description }}</textarea>
                                                @error('description') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Sort order</label>
                                                <input name="sort_order" type="number" min="0" class="input" value="{{ $board->sort_order }}">
                                                @error('sort_order') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" @checked($board->is_active) class="rounded border-slate-300">
                                                Active
                                            </label>
                                            <button type="submit" class="btn-primary btn-sm">Save changes</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('admin.catalog.boards.destroy', $board) }}" class="mt-2 inline-block"
                                          onsubmit="return confirm('Delete the board {{ $board->name }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-slate-500">No boards have been created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif ($tab === 'subjects')
            <form method="POST" action="{{ route('admin.catalog.subjects.store') }}" class="card mt-6 p-6">
                <h2 class="text-lg font-semibold text-slate-900">Add a subject</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="subject-name" class="label">Name</label>
                        <input id="subject-name" name="name" type="text" class="input" value="{{ old('name') }}" required>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="subject-code" class="label">Code</label>
                        <input id="subject-code" name="code" type="text" class="input uppercase" value="{{ old('code') }}" required placeholder="MATH">
                        @error('code') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="subject-slug" class="label">Slug (optional)</label>
                        <input id="subject-slug" name="slug" type="text" class="input" value="{{ old('slug') }}" placeholder="mathematics">
                        @error('slug') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="subject-icon" class="label">Icon (emoji)</label>
                        <input id="subject-icon" name="icon" type="text" class="input" value="{{ old('icon') }}" placeholder="📐">
                        @error('icon') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="subject-sort" class="label">Sort order</label>
                        <input id="subject-sort" name="sort_order" type="number" min="0" class="input" value="{{ old('sort_order', 0) }}">
                        @error('sort_order') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                            Active
                        </label>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label for="subject-description" class="label">Description</label>
                        <textarea id="subject-description" name="description" rows="2" class="input">{{ old('description') }}</textarea>
                        @error('description') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-sm mt-4">Create subject</button>
            </form>

            <div class="table-wrap mt-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Code</th>
                            <th>Slug</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Used by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $subject)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $subject->icon }} {{ $subject->name }}</td>
                                <td class="text-slate-600">{{ $subject->code }}</td>
                                <td class="text-slate-600">{{ $subject->slug }}</td>
                                <td class="text-slate-600">{{ $subject->sort_order }}</td>
                                <td>
                                    <span class="badge {{ $subject->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    @forelse ($usage['subject'][$subject->id] ?? [] as $reference)
                                        <span class="badge mr-1 bg-slate-100 text-slate-700">{{ $reference }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="text-right">
                                    <details class="inline-block text-left">
                                        <summary class="btn-secondary btn-sm cursor-pointer list-none">Edit</summary>
                                        <form method="POST" action="{{ route('admin.catalog.subjects.update', $subject) }}" class="mt-3 w-80 space-y-3 rounded-xl border border-slate-200 bg-white p-4">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="label">Name</label>
                                                <input name="name" type="text" class="input" value="{{ $subject->name }}" required>
                                                @error('name') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Code</label>
                                                <input name="code" type="text" class="input uppercase" value="{{ $subject->code }}" required>
                                                @error('code') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Slug</label>
                                                <input name="slug" type="text" class="input" value="{{ $subject->slug }}">
                                                @error('slug') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Icon</label>
                                                <input name="icon" type="text" class="input" value="{{ $subject->icon }}">
                                                @error('icon') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Description</label>
                                                <textarea name="description" rows="2" class="input">{{ $subject->description }}</textarea>
                                                @error('description') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Sort order</label>
                                                <input name="sort_order" type="number" min="0" class="input" value="{{ $subject->sort_order }}">
                                                @error('sort_order') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" @checked($subject->is_active) class="rounded border-slate-300">
                                                Active
                                            </label>
                                            <button type="submit" class="btn-primary btn-sm">Save changes</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('admin.catalog.subjects.destroy', $subject) }}" class="mt-2 inline-block"
                                          onsubmit="return confirm('Delete the subject {{ $subject->name }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-slate-500">No subjects have been created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <form method="POST" action="{{ route('admin.catalog.classes.store') }}" class="card mt-6 p-6">
                <h2 class="text-lg font-semibold text-slate-900">Add a class standard</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="class-name" class="label">Name</label>
                        <input id="class-name" name="name" type="text" class="input" value="{{ old('name') }}" required placeholder="Class 10">
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="class-code" class="label">Code</label>
                        <input id="class-code" name="code" type="text" class="input uppercase" value="{{ old('code') }}" required placeholder="CLASS_10">
                        @error('code') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="class-stage" class="label">Stage</label>
                        <input id="class-stage" name="stage" type="text" class="input" value="{{ old('stage') }}" placeholder="Secondary">
                        @error('stage') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="class-level" class="label">Level order</label>
                        <input id="class-level" name="level_order" type="number" min="0" max="99" class="input" value="{{ old('level_order', 0) }}">
                        @error('level_order') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="class-sort" class="label">Sort order</label>
                        <input id="class-sort" name="sort_order" type="number" min="0" class="input" value="{{ old('sort_order', 0) }}">
                        @error('sort_order') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                            Active
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-sm mt-4">Create class standard</button>
            </form>

            <div class="table-wrap mt-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Stage</th>
                            <th>Level</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th>Used by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $classStandard)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $classStandard->name }}</td>
                                <td class="text-slate-600">{{ $classStandard->code }}</td>
                                <td class="text-slate-600">{{ $classStandard->stage ?: '—' }}</td>
                                <td class="text-slate-600">{{ $classStandard->level_order }}</td>
                                <td class="text-slate-600">{{ $classStandard->sort_order }}</td>
                                <td>
                                    <span class="badge {{ $classStandard->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $classStandard->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    @forelse ($usage['class'][$classStandard->id] ?? [] as $reference)
                                        <span class="badge mr-1 bg-slate-100 text-slate-700">{{ $reference }}</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="text-right">
                                    <details class="inline-block text-left">
                                        <summary class="btn-secondary btn-sm cursor-pointer list-none">Edit</summary>
                                        <form method="POST" action="{{ route('admin.catalog.classes.update', $classStandard) }}" class="mt-3 w-80 space-y-3 rounded-xl border border-slate-200 bg-white p-4">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="label">Name</label>
                                                <input name="name" type="text" class="input" value="{{ $classStandard->name }}" required>
                                                @error('name') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Code</label>
                                                <input name="code" type="text" class="input uppercase" value="{{ $classStandard->code }}" required>
                                                @error('code') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Stage</label>
                                                <input name="stage" type="text" class="input" value="{{ $classStandard->stage }}">
                                                @error('stage') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Level order</label>
                                                <input name="level_order" type="number" min="0" max="99" class="input" value="{{ $classStandard->level_order }}">
                                                @error('level_order') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="label">Sort order</label>
                                                <input name="sort_order" type="number" min="0" class="input" value="{{ $classStandard->sort_order }}">
                                                @error('sort_order') <p class="error">{{ $message }}</p> @enderror
                                            </div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                                <input type="checkbox" name="is_active" value="1" @checked($classStandard->is_active) class="rounded border-slate-300">
                                                Active
                                            </label>
                                            <button type="submit" class="btn-primary btn-sm">Save changes</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('admin.catalog.classes.destroy', $classStandard) }}" class="mt-2 inline-block"
                                          onsubmit="return confirm('Delete {{ $classStandard->name }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-slate-500">No class standards have been created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
