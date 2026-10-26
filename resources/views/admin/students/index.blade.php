@extends('layouts.app')

@section('title', 'Students — Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <div>
            <h1 class="page-title">Students</h1>
            <p class="page-subtitle">Every child profile on the platform, listed with the linked parent only.</p>
        </div>

        <div class="alert-info mt-4">
            A child's residential address, date of birth and medical notes are never shown here. Contact details are masked so that the list can be shared safely.
        </div>

        <form method="GET" action="{{ route('admin.students.index') }}" class="card mt-6 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div>
                    <label for="q" class="label">Search by name</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Aarav">
                </div>
                <div>
                    <label for="class" class="label">Class</label>
                    <select id="class" name="class" class="input">
                        <option value="">All classes</option>
                        @foreach ($classes as $classStandard)
                            <option value="{{ $classStandard->id }}" @selected((string) $filters['class'] === (string) $classStandard->id)>{{ $classStandard->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="board" class="label">Board</label>
                    <select id="board" name="board" class="input">
                        <option value="">All boards</option>
                        @foreach ($boards as $board)
                            <option value="{{ $board->id }}" @selected((string) $filters['board'] === (string) $board->id)>{{ $board->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary btn-sm">Apply</button>
                    <a href="{{ route('admin.students.index') }}" class="btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-wrap mt-6">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Board</th>
                        <th>Parent</th>
                        <th>Parent contact</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $contact = $parentContacts[$student->id] ?? ['name' => '—', 'contact' => '—'];
                        @endphp
                        <tr>
                            <td class="font-medium text-slate-900">{{ $student->display_name }}</td>
                            <td class="text-slate-600">{{ $student->classStandard?->name ?? '—' }}</td>
                            <td class="text-slate-600">{{ $student->board?->name ?? '—' }}</td>
                            <td class="text-slate-600">{{ $contact['name'] }}</td>
                            <td class="text-slate-600">{{ $contact['contact'] }}</td>
                            <td>
                                <span class="badge {{ $student->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $student->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-slate-500">No student profiles match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $students->links() }}
        </div>
    </div>
@endsection
