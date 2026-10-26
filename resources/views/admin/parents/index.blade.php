@extends('layouts.app')

@section('title', 'Parents — Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <div>
            <h1 class="page-title">Parents</h1>
            <p class="page-subtitle">Every parent account registered on the platform.</p>
        </div>

        <form method="GET" action="{{ route('admin.parents.index') }}" class="card mt-6 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="q" class="label">Search</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="Name, email or mobile number">
                </div>
                <div>
                    <label for="status" class="label">Account status</label>
                    <select id="status" name="status" class="input">
                        <option value="">All accounts</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="suspended" @selected($filters['status'] === 'suspended')>Suspended</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary btn-sm">Apply filters</button>
                <a href="{{ route('admin.parents.index') }}" class="btn-secondary btn-sm">Reset</a>
            </div>
        </form>

        <div class="table-wrap mt-6">
            <table class="table">
                <thead>
                    <tr>
                        <th>Parent</th>
                        <th>Contact</th>
                        <th>City</th>
                        <th>Children</th>
                        <th>Bookings</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parents as $parent)
                        <tr>
                            <td>
                                <p class="font-medium text-slate-900">{{ $parent->user?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $parent->user?->email ?? '—' }}</p>
                            </td>
                            <td class="text-slate-600">
                                @php
                                    $mobile = preg_replace('/\D/', '', (string) ($parent->user?->mobile ?? ''));
                                @endphp
                                {{ $mobile && strlen($mobile) >= 6 ? substr($mobile, 0, 2).'****'.substr($mobile, -3) : '—' }}
                            </td>
                            <td class="text-slate-600">{{ $parent->city ?: '—' }}</td>
                            <td class="text-slate-600">{{ $parent->students_count }}</td>
                            <td class="text-slate-600">{{ $parent->bookings_count }}</td>
                            <td>
                                <span class="badge {{ $parent->user?->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ ucfirst($parent->user?->status ?? 'unknown') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-slate-600">{{ $parent->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.parents.show', $parent) }}" class="btn-secondary btn-sm">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-slate-500">No parents match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $parents->links() }}
        </div>
    </div>
@endsection
