@extends('layouts.app')

@section('title', $teacher->user?->name.' — Teacher')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.partials.nav')

        <a href="{{ route('admin.teachers.index') }}" class="text-sm font-medium text-brand-700 hover:underline">&larr; Back to teachers</a>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="page-title">{{ $teacher->user?->name ?? 'Teacher' }}</h1>
                <p class="page-subtitle">
                    {{ $teacher->user?->email ?? '—' }}
                    @if ($teacher->city) · {{ $teacher->city }}@endif
                    · Joined {{ $teacher->created_at->format('d M Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $statusClass = match ($teacher->verification_status) {
                        'approved' => 'bg-emerald-100 text-emerald-800',
                        'rejected' => 'bg-rose-100 text-rose-800',
                        default => 'bg-amber-100 text-amber-800',
                    };
                @endphp
                <span class="badge {{ $statusClass }}">{{ ucfirst($teacher->verification_status) }}</span>
                <span class="badge {{ $teacher->user?->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                    Account {{ ucfirst($teacher->user?->status ?? 'unknown') }}
                </span>
            </div>
        </div>

        @if ($teacher->rejection_reason)
            <div class="alert-error mt-6">
                <p class="font-semibold">Rejection reason</p>
                <p class="mt-1">{{ $teacher->rejection_reason }}</p>
            </div>
        @endif

        {{-- Moderation actions --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Moderation</h2>
            <p class="text-sm text-slate-500">Approving publishes the teacher in the public directory. Rejecting or suspending keeps the profile hidden and notifies the teacher.</p>

            <div class="mt-4 flex flex-wrap gap-2">
                @if ($teacher->verification_status !== 'approved')
                    <form method="POST" action="{{ route('admin.teachers.verify', $teacher) }}">
                        @csrf
                        <button type="submit" class="btn-success btn-sm">Approve teacher</button>
                    </form>
                @endif

                @if ($teacher->verification_status !== 'rejected')
                    <details class="w-full sm:w-auto">
                        <summary class="btn-danger btn-sm cursor-pointer list-none">Reject teacher</summary>
                        <form method="POST" action="{{ route('admin.teachers.reject', $teacher) }}" class="mt-3 max-w-xl space-y-3">
                            @csrf
                            <div>
                                <label for="reject-reason" class="label">Reason shown to the teacher</label>
                                <textarea id="reject-reason" name="reason" rows="3" class="input" required minlength="10" placeholder="Please upload a legible copy of your degree certificate.">{{ old('reason') }}</textarea>
                                @error('reason') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-danger btn-sm">Reject and notify</button>
                        </form>
                    </details>
                @endif

                @if ($teacher->user?->status === 'active')
                    <details class="w-full sm:w-auto">
                        <summary class="btn-secondary btn-sm cursor-pointer list-none">Suspend account</summary>
                        <form method="POST" action="{{ route('admin.teachers.suspend', $teacher) }}" class="mt-3 max-w-xl space-y-3">
                            @csrf
                            <div>
                                <label for="suspend-reason" class="label">Reason for suspension</label>
                                <textarea id="suspend-reason" name="reason" rows="3" class="input" required minlength="5">{{ old('suspend_reason') }}</textarea>
                                @error('reason') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-secondary btn-sm">Suspend account</button>
                        </form>
                    </details>
                @else
                    <form method="POST" action="{{ route('admin.teachers.reactivate', $teacher) }}">
                        @csrf
                        <button type="submit" class="btn-success btn-sm">Reactivate account</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Profile --}}
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-6 lg:col-span-2">
                <h2 class="text-lg font-semibold text-slate-900">Profile</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Headline</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $teacher->headline ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Qualification summary</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $teacher->qualification_summary ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Experience</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $teacher->experience_years }} years</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Languages</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ implode(', ', $teacher->languages ?: []) ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teaching modes</dt>
                        <dd class="mt-1 text-sm text-slate-800">
                            @if ($teacher->accepts_online) <span class="badge mr-1 bg-slate-100 text-slate-700">Online</span> @endif
                            @if ($teacher->accepts_offline) <span class="badge bg-slate-100 text-slate-700">Offline</span> @endif
                            @if (! $teacher->accepts_online && ! $teacher->accepts_offline) — @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rating</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ number_format((float) $teacher->rating_average, 2) }} ★ ({{ $teacher->rating_count }} reviews)</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sessions completed</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $teacher->completed_sessions_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Verified</dt>
                        <dd class="mt-1 text-sm text-slate-800">
                            {{ $teacher->verified_at?->format('d M Y, h:i A') ?: 'Not verified yet' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Service area</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ number_format((float) $teacher->service_radius_km, 2) }} km around {{ $teacher->city ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Default session</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $teacher->default_session_minutes }} minutes (buffer {{ $teacher->buffer_minutes }} min)</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bio</dt>
                        <dd class="mt-1 text-sm text-slate-800">{{ $teacher->bio ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</dt>
                        <dd class="mt-1 text-sm text-slate-800">
                            {{ collect([$teacher->address_line1, $teacher->city, $teacher->state, $teacher->pincode])->filter()->implode(', ') ?: '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($teacher->teacherSubjects as $link)
                        <span class="badge bg-brand-50 text-brand-700">{{ $link->subject?->name ?? '—' }}</span>
                    @endforeach
                    @foreach ($teacher->teacherBoards as $link)
                        <span class="badge bg-slate-100 text-slate-700">{{ $link->board?->name ?? '—' }}</span>
                    @endforeach
                    @foreach ($teacher->teacherClassStandards as $link)
                        <span class="badge bg-slate-100 text-slate-700">{{ $link->classStandard?->name ?? '—' }}</span>
                    @endforeach
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-semibold text-slate-900">Earnings summary</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Gross</dt><dd class="font-semibold text-slate-900">₹{{ number_format((float) $earningsSummary['gross'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Commission</dt><dd class="font-semibold text-slate-900">₹{{ number_format((float) $earningsSummary['commission'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Refunded</dt><dd class="font-semibold text-slate-900">₹{{ number_format((float) $earningsSummary['refunded'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Net earned</dt><dd class="font-semibold text-slate-900">₹{{ number_format((float) $earningsSummary['net_earned'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Pending settlement</dt><dd class="font-semibold text-amber-700">₹{{ number_format((float) $earningsSummary['pending'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Available</dt><dd class="font-semibold text-emerald-700">₹{{ number_format((float) $earningsSummary['available'], 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Paid out</dt><dd class="font-semibold text-slate-900">₹{{ number_format((float) $earningsSummary['paid'], 2) }}</dd></div>
                </dl>

                <h3 class="mt-6 text-sm font-semibold text-slate-900">Booking counts</h3>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                    @forelse ($bookingCounts as $status => $count)
                        <li class="flex justify-between"><span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span><span>{{ $count }}</span></li>
                    @empty
                        <li>No bookings yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Qualifications --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Qualifications</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Degree</th>
                            <th>Institution</th>
                            <th>Year</th>
                            <th>Status</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teacher->qualifications as $qualification)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $qualification->degree ?: '—' }}</td>
                                <td class="text-slate-600">{{ $qualification->institution ?: '—' }}</td>
                                <td class="text-slate-600">{{ $qualification->year_of_passing ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $qualification->verification_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($qualification->verification_status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ ucfirst($qualification->verification_status) }}
                                    </span>
                                </td>
                                <td class="text-slate-600">{{ $qualification->reviewed_at?->format('d M Y') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-slate-500">No qualifications have been uploaded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Documents --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Documents</h2>
            <p class="text-sm text-slate-500">Approve or reject each uploaded identity or qualification document.</p>

            <div class="mt-4 space-y-4">
                @forelse ($teacher->documents as $document)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $document->title }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ str_replace('_', ' ', $document->doc_type) }}
                                    @if ($document->original_name) · {{ $document->original_name }} @endif
                                    @if ($document->file_size) · {{ number_format((float) $document->file_size / 1024, 1) }} KB @endif
                                </p>
                                @if ($document->remarks)
                                    <p class="mt-1 text-xs text-slate-600">Remarks: {{ $document->remarks }}</p>
                                @endif
                                @if ($document->reviewed_at)
                                    <p class="mt-1 text-xs text-slate-500">Reviewed {{ $document->reviewed_at->format('d M Y, h:i A') }}</p>
                                @endif
                            </div>
                            <span class="badge {{ $document->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($document->status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ ucfirst($document->status) }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('admin.documents.review', $document) }}" class="mt-3 flex flex-wrap items-end gap-2">
                            @csrf
                            <div class="w-40">
                                <label for="doc-status-{{ $document->id }}" class="label">Decision</label>
                                <select id="doc-status-{{ $document->id }}" name="status" class="input">
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                </select>
                            </div>
                            <div class="min-w-[16rem] flex-1">
                                <label for="doc-remarks-{{ $document->id }}" class="label">Remarks (required when rejecting)</label>
                                <input id="doc-remarks-{{ $document->id }}" name="remarks" type="text" class="input" value="{{ old('remarks') }}">
                                @error('remarks') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="btn-primary btn-sm">Save decision</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No documents have been uploaded by this teacher.</p>
                @endforelse
            </div>
        </div>

        {{-- Fees --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Fee rules</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Board</th>
                            <th>Mode</th>
                            <th>Hourly rate</th>
                            <th>Session</th>
                            <th>Travel</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teacher->fees as $fee)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $fee->subject?->name ?? 'All subjects' }}</td>
                                <td class="text-slate-600">{{ $fee->classStandard?->name ?? 'All classes' }}</td>
                                <td class="text-slate-600">{{ $fee->board?->name ?? 'All boards' }}</td>
                                <td class="text-slate-600">{{ ucfirst($fee->mode) }}</td>
                                <td class="text-slate-900">₹{{ number_format((float) $fee->hourly_rate, 2) }}</td>
                                <td class="text-slate-600">{{ $fee->session_minutes }} min</td>
                                <td class="text-slate-600">₹{{ number_format((float) $fee->travel_charge, 2) }}</td>
                                <td class="text-slate-600">{{ $fee->is_active ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-slate-500">This teacher has not published any fee rules yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Availability --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Availability rules</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Weekday</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Mode</th>
                            <th>Session</th>
                            <th>Buffer</th>
                            <th>Effective</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teacher->availabilityRules as $rule)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $rule->weekdayName() }}</td>
                                <td class="text-slate-600">{{ \Illuminate\Support\Str::of($rule->start_time)->substr(0, 5) }}</td>
                                <td class="text-slate-600">{{ \Illuminate\Support\Str::of($rule->end_time)->substr(0, 5) }}</td>
                                <td class="text-slate-600">{{ ucfirst($rule->mode) }}</td>
                                <td class="text-slate-600">{{ $rule->session_minutes }} min</td>
                                <td class="text-slate-600">{{ $rule->buffer_minutes }} min</td>
                                <td class="text-slate-600">
                                    {{ $rule->effective_from?->format('d M Y') ?: 'Always' }}
                                    @if ($rule->effective_to) — {{ $rule->effective_to->format('d M Y') }} @endif
                                </td>
                                <td class="text-slate-600">{{ $rule->is_active ? 'Yes' : 'No' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-slate-500">No availability rules have been set.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Earnings --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Earnings ledger</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Gross</th>
                            <th>Commission</th>
                            <th>Refunded</th>
                            <th>Net</th>
                            <th>Status</th>
                            <th>Available at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($earnings as $earning)
                            <tr>
                                <td class="font-medium text-slate-900">
                                    @if ($earning->booking)
                                        <a href="{{ route('admin.bookings.show', $earning->booking) }}" class="text-brand-700 hover:underline">{{ $earning->booking->reference_no }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-slate-900">₹{{ number_format((float) $earning->gross_amount, 2) }}</td>
                                <td class="text-slate-600">₹{{ number_format((float) $earning->commission_amount, 2) }}</td>
                                <td class="text-slate-600">₹{{ number_format((float) $earning->refund_amount, 2) }}</td>
                                <td class="font-semibold text-slate-900">₹{{ number_format((float) $earning->net_amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $earning->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($earning->status === 'reversed' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ ucfirst($earning->status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap text-slate-600">{{ $earning->available_at?->format('d M Y') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-slate-500">No earnings have been credited yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bookings --}}
        <div class="card mt-6 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Recent bookings</h2>
            <div class="table-wrap mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Student</th>
                            <th>Parent</th>
                            <th>Subject</th>
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
                                <td class="text-slate-600">{{ $booking->student?->display_name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $booking->parent?->user?->name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $booking->subject?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-slate-600">{{ $booking->start_at?->format('d M Y, h:i A') }}</td>
                                <td class="text-slate-900">₹{{ number_format((float) $booking->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $booking->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-slate-500">This teacher has no bookings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
