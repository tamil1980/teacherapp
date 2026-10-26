@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Platform dashboard</h1>
                <p class="page-subtitle">A live summary of teachers, bookings, money and moderation queues.</p>
            </div>
            <p class="text-xs text-slate-500">All times are shown in Asia/Kolkata.</p>
        </div>

        {{-- Key figures --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teachers</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $teacherTotal }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    <span class="badge bg-amber-100 text-amber-800">{{ $teacherCounts['pending'] }} pending</span>
                    <span class="badge mt-1 mr-1 bg-emerald-100 text-emerald-800">{{ $teacherCounts['approved'] }} approved</span>
                    <span class="badge bg-rose-100 text-rose-800">{{ $teacherCounts['rejected'] }} rejected</span>
                </p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Parents & students</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $parentTotal }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $studentTotal }} student profiles</p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bookings</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $bookingTotal }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    @forelse ($bookingCounts as $status => $count)
                        <span class="badge mr-1 bg-slate-100 text-slate-700">{{ $count }} {{ str_replace('_', ' ', $status) }}</span>
                    @empty
                        <span class="badge bg-slate-100 text-slate-700">No bookings yet</span>
                    @endforelse
                </p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gross revenue</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₹{{ number_format((float) $grossRevenue, 2) }}</p>
                <p class="mt-2 text-xs text-slate-500">Net of refunds: ₹{{ number_format((float) $netRevenue, 2) }}</p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Platform commission</p>
                <p class="mt-2 text-3xl font-bold text-emerald-700">₹{{ number_format((float) $commissionEarned, 2) }}</p>
                <p class="mt-2 text-xs text-slate-500">Earned across {{ \App\Models\PlatformCommission::count() }} commission rows.</p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Owed to teachers</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">₹{{ number_format((float) $teacherOwed, 2) }}</p>
                <p class="mt-2 text-xs text-slate-500">Pending settlement plus available balance.</p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending refunds</p>
                <p class="mt-2 text-3xl font-bold text-amber-700">{{ $pendingRefundCount }}</p>
                <p class="mt-2 text-xs text-slate-500">₹{{ number_format((float) $pendingRefundAmount, 2) }} awaiting processing.</p>
            </div>

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending payouts & disputes</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $pendingPayoutCount }} / {{ $openDisputeCount }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    ₹{{ number_format((float) $pendingPayoutAmount, 2) }} in payouts · {{ $openDisputeCount }} open disputes.
                </p>
            </div>
        </div>

        {{-- Revenue by month --}}
        <div class="card mt-6 p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Captured revenue by month</h2>
                    <p class="text-sm text-slate-500">Total captured over the last six months: ₹{{ number_format((float) $revenueTotal, 2) }}</p>
                </div>
            </div>

            @if (count($revenueByMonth) > 0)
                <div class="mt-6 flex items-end gap-3 overflow-x-auto pb-2">
                    @foreach ($revenueByMonth as $month)
                        @php
                            $height = $revenueMax > 0 ? max(2, (int) round(($month['total'] / $revenueMax) * 160)) : 2;
                        @endphp
                        <div class="flex w-20 flex-col items-center gap-2">
                            <span class="text-xs font-semibold text-slate-700">₹{{ number_format((float) $month['total'], 2) }}</span>
                            <div class="flex h-40 w-full items-end rounded-lg bg-slate-100">
                                <div class="w-full rounded-lg bg-brand-500" style="height: {{ $height }}px"
                                     title="{{ $month['label'] }}: ₹{{ number_format((float) $month['total'], 2) }}"></div>
                            </div>
                            <span class="text-xs text-slate-500">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-6 text-sm text-slate-500">No captured payments in the last six months.</p>
            @endif
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent bookings --}}
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Recent bookings</h2>
                    <a href="{{ route('admin.bookings.index') }}" class="btn-ghost btn-sm">View all</a>
                </div>

                <div class="table-wrap mt-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Teacher</th>
                                <th>When</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentBookings as $booking)
                                <tr>
                                    <td class="font-medium text-slate-900">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="text-brand-700 hover:underline">{{ $booking->reference_no }}</a>
                                    </td>
                                    <td class="text-slate-600">{{ $booking->teacher?->user?->name ?? '—' }}</td>
                                    <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $booking->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-slate-500">No bookings have been created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent audit trail --}}
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Recent admin activity</h2>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn-ghost btn-sm">Audit log</a>
                </div>

                <ul class="mt-4 space-y-3">
                    @forelse ($recentAudits as $log)
                        <li class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $log->action }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $log->admin?->name ?? 'System' }}
                                    @if ($log->subject_type)
                                        · {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                    @endif
                                </p>
                            </div>
                            <span class="whitespace-nowrap text-xs text-slate-400">{{ $log->created_at->format('d M, h:i A') }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No administrator actions have been recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
