@extends('layouts.app')

@section('title', ($parent->user?->name ?? 'Parent').' — Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <a href="{{ route('admin.parents.index') }}" class="text-sm font-medium text-brand-700 hover:underline">&larr; Back to parents</a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $parent->user?->name ?? 'Parent' }}</h1>
                <p class="page-subtitle">
                    {{ $parent->user?->email ?? '—' }}
                    @if ($parent->user?->mobile) · {{ $parent->user->mobile }} @endif
                    · Joined {{ $parent->created_at->format('d M Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge {{ $parent->user?->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                    Account {{ ucfirst($parent->user?->status ?? 'unknown') }}
                </span>
                <span class="badge bg-slate-100 text-slate-700">{{ $totals['bookings'] }} bookings</span>
            </div>
        </div>

        @if ($parent->user?->status === 'active')
            <div class="card mt-6 p-6">
                <h2 class="text-lg font-semibold text-slate-900">Account action</h2>
                <p class="text-sm text-slate-500">Suspending blocks the parent from logging in. Existing bookings are not cancelled.</p>

                <details class="mt-4 w-full max-w-xl">
                    <summary class="btn-danger btn-sm cursor-pointer list-none">Suspend this account</summary>
                    <form method="POST" action="{{ route('admin.parents.suspend', $parent) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label for="parent-suspend-reason" class="label">Reason for suspension</label>
                            <textarea id="parent-suspend-reason" name="reason" rows="3" class="input" required minlength="5">{{ old('reason') }}</textarea>
                            @error('reason') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-danger btn-sm">Suspend account</button>
                    </form>
                </details>
            </div>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-6 lg:col-span-1">
                <h2 class="text-lg font-semibold text-slate-900">Profile</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Occupation</dt><dd class="text-right font-medium text-slate-900">{{ $parent->occupation ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">City</dt><dd class="text-right font-medium text-slate-900">{{ $parent->city ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">State</dt><dd class="text-right font-medium text-slate-900">{{ $parent->state ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Pincode</dt><dd class="text-right font-medium text-slate-900">{{ $parent->pincode ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Emergency contact</dt><dd class="text-right font-medium text-slate-900">{{ $parent->emergency_contact ?: '—' }}</dd></div>
                </dl>

                <div class="mt-6 rounded-lg bg-slate-50 p-4 text-xs text-slate-600">
                    <p class="font-semibold text-slate-700">Privacy note</p>
                    <p class="mt-1">The full residential address of a parent is only visible to the parent and is never shown in any list view.</p>
                </div>
            </div>

            <div class="card p-6 lg:col-span-2">
                <h2 class="text-lg font-semibold text-slate-900">Children</h2>
                <p class="text-sm text-slate-500">Only the details needed to service bookings are shown — no address, date of birth or medical notes.</p>

                <div class="table-wrap mt-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Board</th>
                                <th>School</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td class="font-medium text-slate-900">{{ $student->display_name }}</td>
                                    <td class="text-slate-600">{{ $student->classStandard?->name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $student->board?->name ?? '—' }}</td>
                                    <td class="text-slate-600">{{ $student->school_name ?: '—' }}</td>
                                    <td>
                                        <span class="badge {{ $student->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $student->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-slate-500">No child profiles have been created.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Bookings --}}
        <div class="card mt-6 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900">Bookings</h2>
                <p class="text-sm text-slate-500">
                    Paid ₹{{ number_format((float) $totals['paid'], 2) }} ·
                    Refunded ₹{{ number_format((float) $totals['refunded'], 2) }}
                </p>
            </div>

            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Teacher</th>
                            <th>Child</th>
                            <th>When</th>
                            <th>Total</th>
                            <th>Status</th>
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
                                <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                                <td class="text-slate-900">₹{{ number_format((float) $booking->total_amount, 2) }}</td>
                                <td><span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-slate-500">This parent has not booked any classes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payments --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Booking</th>
                            <th>Amount</th>
                            <th>Refunded</th>
                            <th>Method</th>
                            <th>Paid at</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="font-medium text-slate-900">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-brand-700 hover:underline">{{ $payment->reference_no }}</a>
                                </td>
                                <td class="text-slate-600">
                                    @if ($payment->booking)
                                        <a href="{{ route('admin.bookings.show', $payment->booking) }}" class="hover:underline">{{ $payment->booking->reference_no }}</a>
                                    @else — @endif
                                </td>
                                <td class="text-slate-900">₹{{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="text-slate-600">₹{{ number_format((float) $payment->refunded_amount, 2) }}</td>
                                <td class="text-slate-600">{{ $payment->method ?: '—' }}</td>
                                <td class="whitespace-nowrap text-slate-600">{{ $payment->paid_at?->format('d M Y, h:i A') ?: '—' }}</td>
                                <td><span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $payment->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-slate-500">No payments have been made by this parent.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
