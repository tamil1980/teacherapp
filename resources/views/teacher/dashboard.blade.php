@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title">Welcome back, {{ \Illuminate\Support\Str::before($teacher->user->name, ' ') }}</h1>
            <p class="page-subtitle">
                @if ($teacher->headline)
                    {{ $teacher->headline }}
                @else
                    Complete your profile so parents can find you.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.availability.index') }}" class="btn-primary">Set availability</a>
            <a href="{{ route('teacher.bookings.index', ['tab' => 'pending']) }}" class="btn-secondary">Review requests</a>
        </div>
    </div>

    @if ($pendingCount > 0)
        <div class="mt-6 alert-info">
            You have <strong>{{ $pendingCount }}</strong> booking{{ $pendingCount === 1 ? '' : 's' }} waiting for your acceptance.
            <a href="{{ route('teacher.bookings.index', ['tab' => 'pending']) }}" class="font-semibold underline">Review now</a>
        </div>
    @endif

    {{-- KPI cards --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming sessions</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $upcomingCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Confirmed or awaiting acceptance</p>
        </div>

        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending acceptance</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $pendingCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Bookings you still need to confirm</p>
        </div>

        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">This month&rsquo;s earnings</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">₹{{ number_format((float) $monthEarnings, 2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Net of platform commission</p>
        </div>

        <div class="card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completed sessions</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ $completedCount }}</p>
            <p class="mt-1 text-xs text-slate-500">All time</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">

        {{-- Today's sessions --}}
        <div class="lg:col-span-2 space-y-6">
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Today&rsquo;s sessions</h2>
                    <span class="badge bg-slate-100 text-slate-600">{{ $todayBookings->count() }} scheduled</span>
                </div>

                @if ($todayBookings->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">No sessions are scheduled for today.</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($todayBookings as $booking)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $booking->student?->display_name ?? 'Student' }}
                                        @if ($booking->subject)
                                            <span class="font-normal text-slate-500">&middot; {{ $booking->subject->name }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $booking->start_at->format('d M Y, h:i A') }} &rarr; {{ $booking->end_at->format('h:i A') }}
                                        &middot; {{ ucfirst($booking->mode) }}
                                        @if ($booking->classStandard)
                                            &middot; {{ $booking->classStandard->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $booking->status === 'pending_teacher' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ $booking->status === 'pending_teacher' ? 'Awaiting acceptance' : ucfirst(str_replace('_', ' ', $booking->status)) }}
                                    </span>
                                    <a href="{{ route('teacher.bookings.show', $booking) }}" class="btn-secondary btn-sm">Open</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Upcoming sessions --}}
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Next sessions</h2>
                    <a href="{{ route('teacher.bookings.index', ['tab' => 'upcoming']) }}" class="btn-ghost btn-sm">View all</a>
                </div>

                @if ($upcomingBookings->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">You have no upcoming sessions. Publish your availability so parents can book you.</p>
                @else
                    <div class="table-wrap mt-4">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>When</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($upcomingBookings as $booking)
                                    <tr>
                                        <td class="font-medium text-slate-900">{{ $booking->student?->display_name ?? '—' }}</td>
                                        <td>{{ $booking->subject?->name ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $booking->start_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ ucfirst($booking->mode) }}</td>
                                        <td>
                                            <span class="badge {{ $booking->status === 'pending_teacher' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                {{ $booking->status === 'pending_teacher' ? 'Pending' : ucfirst(str_replace('_', ' ', $booking->status)) }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('teacher.bookings.show', $booking) }}" class="btn-secondary btn-sm">Open</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Recent activity --}}
            @if ($recentBookings->isNotEmpty())
                <section class="card p-6">
                    <h2 class="text-lg font-semibold text-slate-900">Recent activity</h2>
                    <div class="mt-4 space-y-2">
                        @foreach ($recentBookings as $booking)
                            <a href="{{ route('teacher.bookings.show', $booking) }}"
                               class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 hover:bg-slate-50">
                                <span class="text-sm text-slate-700">
                                    <span class="font-medium text-slate-900">{{ $booking->student?->display_name ?? 'Student' }}</span>
                                    &middot; {{ $booking->start_at->format('d M Y') }}
                                </span>
                                <span class="badge bg-slate-100 text-slate-600">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Notifications</h2>
                    @if ($unreadNotifications > 0)
                        <span class="badge bg-brand-100 text-brand-700">{{ $unreadNotifications }} new</span>
                    @endif
                </div>

                @if ($notifications->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">You have no notifications yet.</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($notifications as $notification)
                            <li class="border-l-2 {{ $notification->read_at ? 'border-slate-200' : 'border-brand-500' }} pl-3">
                                <p class="text-sm font-medium text-slate-900">{{ $notification->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $notification->message }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $notification->created_at->format('d M Y, h:i A') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a href="{{ route('notifications.index') }}" class="btn-secondary btn-sm mt-4 w-full">All notifications</a>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Quick links</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($quickLinks as $link)
                        <a href="{{ route($link['route']) }}"
                           class="block rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 hover:bg-brand-50/40">
                            <span class="block text-sm font-semibold text-slate-900">{{ $link['label'] }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $link['hint'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Your listing</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Verification</dt>
                        <dd>
                            <span class="badge {{ $teacher->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($teacher->verification_status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Rating</dt>
                        <dd class="font-medium text-slate-900">
                            {{ number_format((float) $teacher->rating_average, 2) }}
                            <span class="font-normal text-slate-500">({{ (int) $teacher->rating_count }} reviews)</span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Free slots (next 7 days)</dt>
                        <dd class="font-medium text-slate-900">{{ $availableSlots }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Net earned (all time)</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) $summary['net_earned'], 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Available for payout</dt>
                        <dd class="font-medium text-slate-900">₹{{ number_format((float) $summary['available'], 2) }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</div>
@endsection
