@extends('layouts.app')

@section('title', 'My Children')

@section('content')
    @php
        $activeStatuses = ['pending_payment', 'pending_teacher', 'confirmed', 'ongoing'];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">My children</h1>
                <p class="page-subtitle">Child profiles are used to match teachers and book classes.</p>
            </div>
            <a href="{{ route('parent.students.create') }}" class="btn-primary">+ Add child</a>
        </div>

        <div class="mt-6">
            @if ($students->isEmpty())
                <div class="card px-6 py-16 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">No child profiles yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        Add your child's class, board and learning needs so we can suggest the right teachers.
                    </p>
                    <a href="{{ route('parent.students.create') }}" class="btn-primary mt-6">+ Add child profile</a>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Child</th>
                                <th>Class</th>
                                <th>Board</th>
                                <th>Learning requirements</th>
                                <th>Active bookings</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                @php
                                    $activeBookings = $student->bookings()->whereIn('status', $activeStatuses)->count();
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('parent.students.show', $student) }}" class="font-semibold text-slate-900 hover:text-brand-600">
                                            {{ $student->display_name }}
                                        </a>
                                        @if ($student->school_name)
                                            <p class="text-xs text-slate-500">{{ $student->school_name }}</p>
                                        @endif
                                    </td>
                                    <td class="text-slate-600">{{ $student->classStandard?->name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $student->board?->name ?? '—' }}</td>
                                    <td class="max-w-xs text-slate-600">
                                        @if ($student->learning_requirements)
                                            <p class="truncate" title="{{ $student->learning_requirements }}">
                                                {{ \Illuminate\Support\Str::limit($student->learning_requirements, 70) }}
                                            </p>
                                        @else
                                            <span class="text-slate-400">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($activeBookings > 0)
                                            <span class="badge bg-emerald-100 text-emerald-700">{{ $activeBookings }}</span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <a href="{{ route('parent.students.show', $student) }}" class="btn btn-sm btn-secondary">View</a>
                                            <a href="{{ route('parent.students.edit', $student) }}" class="btn btn-sm btn-secondary">Edit</a>

                                            <form method="POST" action="{{ route('parent.students.destroy', $student) }}"
                                                  onsubmit="return confirm('Remove the child profile &quot;{{ $student->display_name }}&quot;? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
