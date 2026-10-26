@extends('layouts.app')

@section('title', 'Booking '.$booking->reference_no)

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <a href="{{ route('admin.bookings.index') }}" class="text-sm font-medium text-brand-700 hover:underline">&larr; Back to bookings</a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $booking->reference_no }}</h1>
                <p class="page-subtitle">
                    {{ ucfirst($booking->mode) }} class ·
                    {{ $booking->start_at?->format('d M Y, h:i A') }} →
                    {{ $booking->end_at?->format('h:i A') }} ·
                    {{ $booking->duration_minutes }} minutes
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $booking->status) }}</span>
                <span class="badge {{ $booking->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    Payment: {{ str_replace('_', ' ', $booking->payment_status) }}
                </span>
            </div>
        </div>

        {{-- Summary --}}
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Session</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Teacher</dt>
                        <dd class="text-right font-medium text-slate-900">
                            @if ($booking->teacher)
                                <a href="{{ route('admin.teachers.show', $booking->teacher) }}" class="text-brand-700 hover:underline">{{ $booking->teacher->user?->name }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Parent</dt><dd class="text-right font-medium text-slate-900">{{ $booking->parent?->user?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Student</dt><dd class="text-right font-medium text-slate-900">{{ $booking->student?->display_name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Class</dt><dd class="text-right font-medium text-slate-900">{{ $booking->classStandard?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Board</dt><dd class="text-right font-medium text-slate-900">{{ $booking->board?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Subject</dt><dd class="text-right font-medium text-slate-900">{{ $booking->subject?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Location</dt><dd class="text-right font-medium text-slate-900">{{ $booking->location?->label ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Attendance</dt><dd class="text-right font-medium text-slate-900">{{ $booking->attendance_status ? ucfirst($booking->attendance_status) : '—' }}</dd></div>
                </dl>

                @if ($booking->notes)
                    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booking note</p>
                        <p class="mt-1">{{ $booking->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Money</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Hourly rate</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->hourly_rate, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->subtotal, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Travel charge</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->travel_charge, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Discount{{ $booking->discount_code ? ' ('.$booking->discount_code.')' : '' }}</dt><dd class="font-medium text-slate-900">−₹{{ number_format((float) $booking->discount_amount, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->tax_amount, 2) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-3"><dt class="font-semibold text-slate-700">Total</dt><dd class="text-lg font-bold text-slate-900">₹{{ number_format((float) $booking->total_amount, 2) }}</dd></div>
                </dl>

                @if ($commission)
                    <h3 class="mt-6 text-sm font-semibold text-slate-900">Commission split</h3>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Gross</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $commission->gross_amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Commission ({{ number_format((float) $commission->commission_percent, 3) }}%)</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $commission->commission_amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Platform</dt><dd class="font-medium text-emerald-700">₹{{ number_format((float) $commission->platform_amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Teacher</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $commission->teacher_amount, 2) }}</dd></div>
                    </dl>
                @else
                    <p class="mt-4 text-xs text-slate-500">No commission has been recorded yet — it is created when the session is completed.</p>
                @endif

                @if ($earning)
                    <h3 class="mt-6 text-sm font-semibold text-slate-900">Teacher earning</h3>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Net</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $earning->net_amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="font-medium text-slate-900">{{ ucfirst($earning->status) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Available at</dt><dd class="font-medium text-slate-900">{{ $earning->available_at?->format('d M Y, h:i A') ?: '—' }}</dd></div>
                    </dl>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Payment</h2>
                @if ($payment)
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Reference</dt>
                            <dd class="text-right font-medium text-slate-900">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="text-brand-700 hover:underline">{{ $payment->reference_no }}</a>
                            </dd>
                        </div>
                        <div class="flex justify-between"><dt class="text-slate-500">Amount</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $payment->amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Refunded</dt><dd class="font-medium text-slate-900">₹{{ number_format((float) $payment->refunded_amount, 2) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Method</dt><dd class="font-medium text-slate-900">{{ $payment->method ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Gateway</dt><dd class="font-medium text-slate-900">{{ $payment->gateway }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Paid at</dt><dd class="font-medium text-slate-900">{{ $payment->paid_at?->format('d M Y, h:i A') ?: '—' }}</dd></div>
                    </dl>
                @else
                    <p class="mt-4 text-sm text-slate-500">No payment has been recorded for this booking.</p>
                @endif

                <h3 class="mt-6 text-sm font-semibold text-slate-900">Refunds</h3>
                @if ($booking->refunds->isEmpty())
                    <p class="mt-2 text-sm text-slate-500">No refunds have been issued.</p>
                @else
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach ($booking->refunds as $refund)
                            <li class="flex justify-between gap-3 border-b border-slate-100 pb-2 last:border-0">
                                <span class="text-slate-700">{{ $refund->reference_no }} <span class="text-xs text-slate-500">({{ $refund->reason }})</span></span>
                                <span class="font-medium text-slate-900">₹{{ number_format((float) $refund->amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Administrator actions --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Administrator actions</h2>
            <p class="text-sm text-slate-500">Every action is written to the audit trail and, where relevant, notifies the parents and teachers involved.</p>

            <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Override the status</h3>
                    <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="override-status" class="label">New status</label>
                            <select id="override-status" name="status" class="input" required>
                                @foreach ($overridableStatuses as $status)
                                    <option value="{{ $status }}" @selected($booking->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="override-note" class="label">Note (recorded in the status history)</label>
                            <textarea id="override-note" name="note" rows="3" class="input" required minlength="5">{{ old('note') }}</textarea>
                            @error('note') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-primary btn-sm">Apply status override</button>
                    </form>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Complete the session</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Completing the booking records the commission and credits the teacher's earnings. It can only be done for a confirmed or ongoing session.
                    </p>
                    @if (in_array($booking->status, [\App\Models\Booking::STATUS_CONFIRMED, \App\Models\Booking::STATUS_ONGOING], true))
                        <form method="POST" action="{{ route('admin.bookings.complete', $booking) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn-success btn-sm">Mark as completed &amp; credit earnings</button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-amber-700">This booking cannot be completed from its current status ({{ str_replace('_', ' ', $booking->status) }}).</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Status history --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Status history</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Actor</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($booking->statusHistories->sortByDesc('created_at') as $history)
                            <tr>
                                <td class="whitespace-nowrap text-slate-600">{{ $history->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-slate-600">{{ $history->from_status ? str_replace('_', ' ', $history->from_status) : '—' }}</td>
                                <td class="font-medium text-slate-900">{{ str_replace('_', ' ', $history->to_status) }}</td>
                                <td class="text-slate-600">{{ $history->actor?->name ?? 'System' }}@if ($history->actor_role) <span class="text-xs text-slate-400">({{ $history->actor_role }})</span> @endif</td>
                                <td class="text-slate-600">{{ $history->note ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-slate-500">No status changes have been recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
