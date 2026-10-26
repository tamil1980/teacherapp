@extends('layouts.app')

@section('title', 'Bookings — Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <div>
            <h1 class="page-title">Bookings</h1>
            <p class="page-subtitle">Every tuition session booked on the platform.</p>
        </div>

        <form method="GET" action="{{ route('admin.bookings.index') }}" class="card mt-6 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div>
                    <label for="q" class="label">Reference</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" class="input" placeholder="EB2601ABCDE">
                </div>
                <div>
                    <label for="status" class="label">Status</label>
                    <select id="status" name="status" class="input">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Booking::query()->select('status')->distinct()->pluck('status') as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="mode" class="label">Mode</label>
                    <select id="mode" name="mode" class="input">
                        <option value="">Online & offline</option>
                        <option value="online" @selected($filters['mode'] === 'online')>Online</option>
                        <option value="offline" @selected($filters['mode'] === 'offline')>Offline</option>
                    </select>
                </div>
                <div>
                    <label for="teacher" class="label">Teacher</label>
                    <select id="teacher" name="teacher" class="input">
                        <option value="">All teachers</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((string) $filters['teacher'] === (string) $teacher->id)>{{ $teacher->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from" class="label">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="input">
                </div>
                <div>
                    <label for="to" class="label">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="input">
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" class="btn-primary btn-sm">Apply filters</button>
                <a href="{{ route('admin.bookings.index') }}" class="btn-secondary btn-sm">Reset</a>
            </div>
        </form>

        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            @foreach ($statusCounts as $status => $count)
                <span class="badge bg-slate-100 text-slate-700">{{ $count }} {{ str_replace('_', ' ', $status) }}</span>
            @endforeach
        </div>

        <div class="table-wrap mt-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Teacher</th>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>When</th>
                        <th>Mode</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="font-medium text-slate-900">
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="text-brand-700 hover:underline">{{ $booking->reference_no }}</a>
                            </td>
                            <td class="text-slate-600">{{ $booking->teacher?->user?->name ?? '—' }}</td>
                            <td class="text-slate-600">{{ $booking->student?->display_name ?? '—' }}</td>
                            <td class="text-slate-600">{{ $booking->subject?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                            <td class="text-slate-600">{{ ucfirst($booking->mode) }}</td>
                            <td class="text-slate-900">₹{{ number_format((float) $booking->total_amount, 2) }}</td>
                            <td class="text-slate-600">{{ str_replace('_', ' ', $booking->payment_status) }}</td>
                            <td><span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            <td>
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="btn-secondary btn-sm">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-slate-500">No bookings match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    </div>
@endsection
