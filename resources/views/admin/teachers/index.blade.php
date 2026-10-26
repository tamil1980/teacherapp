@extends('layouts.app')

@section('title', 'Teachers — Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Teachers</h1>
                <p class="page-subtitle">Verify, moderate and suspend the teachers registered on the platform.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="badge bg-amber-100 text-amber-800">{{ $statusCounts['pending'] ?? 0 }} pending</span>
                <span class="badge bg-emerald-100 text-emerald-800">{{ $statusCounts['approved'] ?? 0 }} approved</span>
                <span class="badge bg-rose-100 text-rose-800">{{ $statusCounts['rejected'] ?? 0 }} rejected</span>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.teachers.index') }}" class="card mt-6 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label for="q" class="label">Search</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Name, email or mobile number">
                </div>
                <div>
                    <label for="status" class="label">Verification status</label>
                    <select id="status" name="status" class="input">
                        <option value="">All statuses</option>
                        <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                        <option value="approved" @selected($filters['status'] === 'approved')>Approved</option>
                        <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div>
                    <label for="city" class="label">City</label>
                    <input id="city" name="city" type="search" value="{{ $filters['city'] }}" class="input" placeholder="Chennai">
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary btn-sm">Apply filters</button>
                <a href="{{ route('admin.teachers.index') }}" class="btn-secondary btn-sm">Reset</a>
            </div>
        </form>

        <div class="table-wrap mt-6">
            <table class="table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Subjects</th>
                        <th>Boards</th>
                        <th>Status</th>
                        <th>City</th>
                        <th>Bookings</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        @php
                            $statusClass = match ($teacher->verification_status) {
                                'approved' => 'bg-emerald-100 text-emerald-800',
                                'rejected' => 'bg-rose-100 text-rose-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <tr>
                            <td>
                                <p class="font-medium text-slate-900">{{ $teacher->user?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $teacher->user?->email ?? '—' }}</p>
                                @if ($teacher->user?->status !== 'active')
                                    <span class="badge mt-1 bg-rose-100 text-rose-800">Account {{ $teacher->user?->status }}</span>
                                @endif
                            </td>
                            <td class="text-slate-600">
                                @foreach ($teacher->teacherSubjects->take(3) as $link)
                                    <span class="badge mr-1 bg-slate-100 text-slate-700">{{ $link->subject?->name ?? '—' }}</span>
                                @endforeach
                                @if ($teacher->teacherSubjects->count() > 3)
                                    <span class="text-xs text-slate-500">+{{ $teacher->teacherSubjects->count() - 3 }}</span>
                                @endif
                            </td>
                            <td class="text-slate-600">
                                @foreach ($teacher->teacherBoards->take(3) as $link)
                                    <span class="badge mr-1 bg-slate-100 text-slate-700">{{ $link->board?->name ?? '—' }}</span>
                                @endforeach
                                @if ($teacher->teacherBoards->count() > 3)
                                    <span class="text-xs text-slate-500">+{{ $teacher->teacherBoards->count() - 3 }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ ucfirst($teacher->verification_status) }}</span>
                            </td>
                            <td class="text-slate-600">{{ $teacher->city ?: '—' }}</td>
                            <td class="text-slate-600">{{ $teacher->bookings_count }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $teacher->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn-secondary btn-sm">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-slate-500">No teachers match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $teachers->links() }}
        </div>
    </div>
@endsection
