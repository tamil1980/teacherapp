@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
    @php
        $statusClasses = [
            'pending_payment' => 'bg-amber-100 text-amber-700',
            'pending_teacher' => 'bg-amber-100 text-amber-700',
            'confirmed' => 'bg-emerald-100 text-emerald-700',
            'ongoing' => 'bg-brand-100 text-brand-700',
            'completed' => 'bg-sky-100 text-sky-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
            'refunded' => 'bg-slate-100 text-slate-600',
            'disputed' => 'bg-orange-100 text-orange-700',
        ];

        $statusLabels = [
            'pending_payment' => 'Payment pending',
            'pending_teacher' => 'Awaiting teacher',
            'confirmed' => 'Confirmed',
            'ongoing' => 'In progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'disputed' => 'Disputed',
        ];

        $paymentClasses = [
            'unpaid' => 'bg-amber-100 text-amber-700',
            'paid' => 'bg-emerald-100 text-emerald-700',
            'partially_refunded' => 'bg-sky-100 text-sky-700',
            'refunded' => 'bg-slate-100 text-slate-600',
        ];

        $paymentLabels = [
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
            'partially_refunded' => 'Partly refunded',
            'refunded' => 'Refunded',
        ];

        $modeClasses = [
            'online' => 'bg-violet-100 text-violet-700',
            'offline' => 'bg-teal-100 text-teal-700',
        ];

        $currentStatus = request('status');
        $isAll = ! $currentStatus && ! request()->boolean('upcoming') && ! request()->boolean('past');

        $tabs = [
            ['label' => 'All', 'url' => route('parent.bookings.index'), 'active' => $isAll],
            ['label' => 'Upcoming', 'url' => route('parent.bookings.index', ['upcoming' => 1]), 'active' => request()->boolean('upcoming')],
            ['label' => 'Past', 'url' => route('parent.bookings.index', ['past' => 1]), 'active' => request()->boolean('past')],
            ['label' => 'Cancelled', 'url' => route('parent.bookings.index', ['status' => 'cancelled']), 'active' => $currentStatus === 'cancelled'],
            ['label' => 'Completed', 'url' => route('parent.bookings.index', ['status' => 'completed']), 'active' => $currentStatus === 'completed'],
        ];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">My bookings</h1>
                <p class="page-subtitle">{{ $bookings->total() }} booking{{ $bookings->total() === 1 ? '' : 's' }} found.</p>
            </div>
            <a href="{{ route('teachers.index') }}" class="btn-primary">+ Book a new class</a>
        </div>

        {{-- Filter tabs --}}
        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Booking filters">
            @foreach ($tabs as $tab)
                <a href="{{ $tab['url'] }}"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition
                          {{ $tab['active']
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Search --}}
        <form method="GET" action="{{ route('parent.bookings.index') }}" class="mt-4 flex flex-wrap items-center gap-3">
            @if ($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            @if (request()->boolean('upcoming'))
                <input type="hidden" name="upcoming" value="1">
            @endif
            @if (request()->boolean('past'))
                <input type="hidden" name="past" value="1">
            @endif

            <div class="relative w-full max-w-sm">
                <label for="booking-search" class="sr-only">Search by reference number</label>
                <input id="booking-search" name="q" type="search" value="{{ request('q') }}"
                       placeholder="Search by reference number…"
                       class="input pl-9" autocomplete="off">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                </svg>
            </div>

            <button type="submit" class="btn-secondary">Search</button>

            @if (request()->filled('q'))
                <a href="{{ route('parent.bookings.index') }}" class="btn-ghost">Clear</a>
            @endif

            <p id="booking-search-count" class="text-xs text-slate-500"></p>
        </form>

        <div class="mt-6">
            @if ($bookings->isEmpty())
                <div class="card px-6 py-16 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">No bookings found</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        There are no bookings for this filter. Browse verified teachers to book your first class.
                    </p>
                    <a href="{{ route('teachers.index') }}" class="btn-primary mt-6">Find a teacher</a>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Teacher</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Date &amp; time</th>
                                <th>Mode</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="text-right">Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bookings as $booking)
                                <tr data-reference="{{ $booking->reference_no }}">
                                    <td class="font-mono text-xs text-slate-600">{{ $booking->reference_no }}</td>
                                    <td class="font-medium text-slate-900">{{ $booking->teacher?->user?->name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $booking->subject?->name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $booking->classStandard?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <span class="badge {{ $modeClasses[$booking->mode] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $booking->mode === 'online' ? 'Online' : 'Offline' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $paymentClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $paymentLabels[$booking->payment_status] ?? ucfirst(str_replace('_', ' ', (string) $booking->payment_status)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap text-right font-medium text-slate-900">
                                        ₹{{ number_format((float) $booking->total_amount, 2) }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('parent.bookings.show', $booking) }}" class="btn btn-sm btn-secondary">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="booking-search-empty" class="card mt-4 hidden px-6 py-10 text-center text-sm text-slate-500">
                    No booking on this page matches that reference number.
                </div>

                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('booking-search');
            var rows = Array.prototype.slice.call(document.querySelectorAll('tbody tr[data-reference]'));
            var empty = document.getElementById('booking-search-empty');
            var counter = document.getElementById('booking-search-count');

            if (!input) {
                return;
            }

            function apply() {
                var query = input.value.trim().toLowerCase();
                var visible = 0;

                rows.forEach(function (row) {
                    var reference = (row.getAttribute('data-reference') || '').toLowerCase();
                    var matches = query === '' || reference.indexOf(query) !== -1;
                    row.style.display = matches ? '' : 'none';
                    if (matches) {
                        visible += 1;
                    }
                });

                if (empty) {
                    empty.classList.toggle('hidden', visible !== 0 || rows.length === 0);
                }

                if (counter) {
                    counter.textContent = query === ''
                        ? ''
                        : visible + ' of ' + rows.length + ' booking' + (rows.length === 1 ? '' : 's') + ' on this page match';
                }
            }

            input.addEventListener('input', apply);
            apply();
        })();
    </script>
@endpush
