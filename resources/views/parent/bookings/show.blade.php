@extends('layouts.app')

@section('title', 'Booking ' . $booking->reference_no)

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
            'pending_teacher' => 'Awaiting teacher acceptance',
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

        $isConfirmedOrLater = in_array($booking->status, ['confirmed', 'ongoing', 'completed'], true);
        $meetingUnlocked = $booking->payment_status === 'paid' && $isConfirmedOrLater;
        $joinUrl = $booking->onlineSession?->join_url;
        $showJoinLink = $meetingUnlocked && ! empty($joinUrl);

        $receiptPayment = $booking->payments
            ->sortByDesc('id')
            ->first(fn ($payment) => in_array($payment->status, ['paid', 'partially_refunded', 'refunded'], true))
            ?? $booking->payments->sortByDesc('id')->first();

        $canMarkComplete = in_array($booking->status, ['confirmed', 'ongoing'], true)
            && $booking->end_at !== null
            && $booking->end_at->isPast();

        $teacherVerified = ($booking->teacher?->verification_status ?? null) === 'approved';
        $durationLabel = (int) $booking->duration_minutes;
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booking</p>
                <h1 class="page-title font-mono">{{ $booking->reference_no }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="badge {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', (string) $booking->status)) }}
                    </span>
                    <span class="badge {{ $paymentClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                        Payment: {{ $paymentLabels[$booking->payment_status] ?? ucfirst(str_replace('_', ' ', (string) $booking->payment_status)) }}
                    </span>
                    <span class="badge {{ $modeClasses[$booking->mode] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $booking->mode === 'online' ? 'Online class' : 'Offline / home tuition' }}
                    </span>
                </div>
            </div>
            <a href="{{ route('parent.bookings.index') }}" class="btn-secondary">← Back to bookings</a>
        </div>

        @if ($booking->status === 'cancelled')
            <div class="alert-warning mt-6">
                <p class="font-semibold">This booking was cancelled.</p>
                @if ($booking->cancel_reason)
                    <p class="mt-1">Reason: {{ $booking->cancel_reason }}</p>
                @endif
                @if ($booking->cancelled_at)
                    <p class="mt-1 text-xs">Cancelled on {{ $booking->cancelled_at->format('d M Y, h:i A') }}</p>
                @endif
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Left column --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Teacher --}}
                <div class="card p-5">
                    <h2 class="text-base font-semibold text-slate-900">Teacher</h2>
                    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="flex flex-wrap items-center gap-2 text-lg font-semibold text-slate-900">
                                {{ $booking->teacher?->user?->name ?? '—' }}
                                @if ($teacherVerified)
                                    <span class="badge bg-emerald-100 text-emerald-700">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                        Verified
                                    </span>
                                @else
                                    <span class="badge bg-amber-100 text-amber-700">Verification pending</span>
                                @endif
                            </p>
                            @if ($booking->teacher?->headline)
                                <p class="mt-1 text-sm text-slate-600">{{ $booking->teacher->headline }}</p>
                            @endif
                            <p class="mt-2 text-sm text-slate-500">
                                @if ($booking->teacher?->rating_count)
                                    ★ {{ number_format((float) $booking->teacher->rating_average, 1) }}
                                    ({{ $booking->teacher->rating_count }} rating{{ $booking->teacher->rating_count === 1 ? '' : 's' }})
                                @endif
                                @if ($booking->teacher?->experience_years)
                                    <span class="text-slate-400">·</span> {{ $booking->teacher->experience_years }} yrs experience
                                @endif
                            </p>
                        </div>
                        @if ($booking->teacher)
                            <a href="{{ route('teachers.show', $booking->teacher) }}" class="btn btn-sm btn-secondary">View teacher profile</a>
                        @endif
                    </div>
                </div>

                {{-- Class details --}}
                <div class="card p-5">
                    <h2 class="text-base font-semibold text-slate-900">Class details</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Student</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $booking->student?->display_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $booking->subject?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Class</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $booking->classStandard?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Board</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $booking->board?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date &amp; time</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $booking->start_at?->format('d M Y, h:i A') }}</dd>
                            @if ($booking->end_at)
                                <dd class="text-xs text-slate-500">to {{ $booking->end_at->format('h:i A') }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Duration</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $durationLabel }} minutes</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mode</dt>
                            <dd class="mt-1 text-sm text-slate-800">
                                {{ $booking->mode === 'online' ? 'Online class' : 'Offline / home tuition' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booked on</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $booking->created_at?->format('d M Y, h:i A') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Meeting / location --}}
                @if ($booking->mode === 'online')
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Online meeting</h2>

                        @if ($showJoinLink)
                            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-sm font-semibold text-emerald-800">Your class is ready to join.</p>
                                <p class="mt-1 text-sm text-emerald-700">
                                    Provider: {{ $booking->onlineSession?->provider ?: 'online' }}
                                    @if ($booking->onlineSession?->meeting_id)
                                        <span class="text-emerald-600">· Meeting ID: {{ $booking->onlineSession->meeting_id }}</span>
                                    @endif
                                </p>
                                @if ($booking->onlineSession?->passcode)
                                    <p class="mt-1 text-sm text-emerald-700">Passcode: {{ $booking->onlineSession->passcode }}</p>
                                @endif
                                <a href="{{ $joinUrl }}" target="_blank" rel="noopener noreferrer" class="btn-primary btn-sm mt-4">
                                    Join online class
                                </a>
                            </div>
                        @else
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                The meeting link will be available once the booking is confirmed and paid.
                            </div>
                        @endif
                    </div>
                @else
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Class location</h2>

                        @if ($booking->location)
                            @if ($isConfirmedOrLater)
                                <address class="mt-4 not-italic text-sm leading-relaxed text-slate-700">
                                    <span class="block font-medium text-slate-900">{{ $booking->location->label }}</span>
                                    @if ($booking->location->address_line1)
                                        <span class="block">{{ $booking->location->address_line1 }}</span>
                                    @endif
                                    @if ($booking->location->area)
                                        <span class="block">{{ $booking->location->area }}</span>
                                    @endif
                                    <span class="block">
                                        {{ $booking->location->city }}
                                        @if ($booking->location->state), {{ $booking->location->state }} @endif
                                        @if ($booking->location->pincode) — {{ $booking->location->pincode }} @endif
                                    </span>
                                </address>
                            @else
                                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    <p class="font-medium">{{ $booking->location->label }}</p>
                                    <p class="mt-1">The full address will be shared once the booking is confirmed.</p>
                                </div>
                            @endif
                        @else
                            <p class="mt-4 text-sm text-slate-500">No location has been set for this class yet.</p>
                        @endif
                    </div>
                @endif

                {{-- Review --}}
                @if ($booking->status === 'completed' && ! $booking->review)
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Rate your teacher</h2>
                        <p class="mt-1 text-sm text-slate-500">Your feedback helps other parents choose with confidence.</p>

                        <form method="POST" action="{{ route('parent.reviews.store', $booking) }}" class="mt-5 space-y-5">
                            @csrf

                            <fieldset>
                                <legend class="label">Rating <span class="text-rose-500">*</span></legend>
                                <div class="flex flex-wrap gap-4">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="radio" name="rating" value="{{ $i }}" @checked((int) old('rating') === $i)
                                                   @if ($i === 1 && old('rating') === null && $loop->first) required @endif
                                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                            {{ $i }} star{{ $i === 1 ? '' : 's' }}
                                        </label>
                                    @endfor
                                </div>
                                @error('rating') <p class="error">{{ $message }}</p> @enderror
                            </fieldset>

                            <div>
                                <label for="review-title" class="label">Headline</label>
                                <input id="review-title" name="title" type="text" value="{{ old('title') }}" maxlength="120"
                                       class="input" placeholder="e.g. Excellent concept teaching">
                                @error('title') <p class="error">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="review-comment" class="label">Your review</label>
                                <textarea id="review-comment" name="comment" rows="4" maxlength="2000"
                                          class="input" placeholder="Share what went well…">{{ old('comment') }}</textarea>
                                @error('comment') <p class="error">{{ $message }}</p> @enderror
                            </div>

                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                Post this review anonymously
                            </label>

                            <button type="submit" class="btn-primary">Submit review</button>
                        </form>
                    </div>
                @elseif ($booking->review)
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Your review</h2>

                        <div class="mt-4">
                            <p class="text-sm font-semibold text-amber-600">
                                {{ str_repeat('★', (int) $booking->review->rating) }}
                                <span class="text-slate-300">{{ str_repeat('★', max(0, 5 - (int) $booking->review->rating)) }}</span>
                                <span class="ml-1 font-normal text-slate-500">{{ $booking->review->rating }} / 5</span>
                            </p>

                            @if ($booking->review->title)
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $booking->review->title }}</p>
                            @endif

                            @if ($booking->review->comment)
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $booking->review->comment }}</p>
                            @endif

                            <p class="mt-3 text-xs text-slate-500">
                                {{ $booking->review->is_anonymous ? 'Posted anonymously' : 'Posted by you' }}
                                @if ($booking->review->created_at)
                                    · {{ $booking->review->created_at->format('d M Y') }}
                                @endif
                            </p>
                        </div>

                        @if ($booking->review->teacher_reply)
                            <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-900">
                                    Reply from {{ $booking->teacher?->user?->name ?? 'the teacher' }}
                                    @if ($booking->review->replied_at)
                                        <span class="ml-1 text-xs font-normal text-slate-500">{{ $booking->review->replied_at->format('d M Y, h:i A') }}</span>
                                    @endif
                                </p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $booking->review->teacher_reply }}</p>
                            </div>
                        @endif

                        <a href="{{ route('parent.reviews.index') }}" class="btn btn-sm btn-secondary mt-5">Manage my reviews</a>
                    </div>
                @endif

                {{-- Status history --}}
                @if ($booking->statusHistories->isNotEmpty())
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Status history</h2>
                        <ol class="mt-4 space-y-3">
                            @foreach ($booking->statusHistories->sortByDesc('created_at') as $history)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                                    <div>
                                        <p class="text-sm text-slate-800">
                                            <span class="font-semibold">{{ $statusLabels[$history->to_status] ?? ucfirst(str_replace('_', ' ', (string) $history->to_status)) }}</span>
                                            @if ($history->from_status)
                                                <span class="text-slate-500">from {{ $statusLabels[$history->from_status] ?? ucfirst(str_replace('_', ' ', (string) $history->from_status)) }}</span>
                                            @endif
                                        </p>
                                        @if ($history->note)
                                            <p class="text-xs text-slate-500">{{ $history->note }}</p>
                                        @endif
                                        <p class="text-xs text-slate-400">{{ $history->created_at?->format('d M Y, h:i A') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                {{-- Price breakdown --}}
                <div class="card p-5">
                    <h2 class="text-base font-semibold text-slate-900">Price breakdown</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-600">
                                ₹{{ number_format((float) $booking->hourly_rate, 2) }}/hr
                                <span class="text-slate-400">× {{ $durationLabel }} min</span>
                            </dt>
                            <dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->subtotal, 2) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-600">Travel / visiting charge</dt>
                            <dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->travel_charge, 2) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-600">
                                Discount
                                @if ($booking->discount_code)
                                    <span class="text-xs text-slate-400">({{ $booking->discount_code }})</span>
                                @endif
                            </dt>
                            <dd class="font-medium text-emerald-700">
                                −₹{{ number_format((float) $booking->discount_amount, 2) }}
                            </dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-600">Tax</dt>
                            <dd class="font-medium text-slate-900">₹{{ number_format((float) $booking->tax_amount, 2) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 border-t border-slate-200 pt-3">
                            <dt class="font-semibold text-slate-900">Total</dt>
                            <dd class="text-lg font-bold text-slate-900">₹{{ number_format((float) $booking->total_amount, 2) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm text-slate-600">Payment status</p>
                            <span class="badge {{ $paymentClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $paymentLabels[$booking->payment_status] ?? ucfirst(str_replace('_', ' ', (string) $booking->payment_status)) }}
                            </span>
                        </div>

                        @if ($receiptPayment)
                            <a href="{{ route('parent.payments.receipt', $receiptPayment) }}" class="btn-secondary btn-sm mt-3 w-full">
                                View receipt
                            </a>
                            <a href="{{ route('parent.payments.show', $receiptPayment) }}" class="btn-ghost btn-sm mt-1 w-full">
                                Payment details
                            </a>
                        @else
                            <p class="mt-2 text-xs text-slate-500">No payment has been recorded for this booking yet.</p>
                        @endif
                    </div>
                </div>

                {{-- Attendance --}}
                <div class="card p-5">
                    <h2 class="text-base font-semibold text-slate-900">Attendance</h2>
                    @if ($booking->attendance)
                        <p class="mt-1 text-sm text-slate-600">
                            Recorded as
                            <span class="font-semibold text-slate-900">{{ ucfirst((string) $booking->attendance->status) }}</span>
                            @if ($booking->attendance->marked_at)
                                <span class="text-xs text-slate-500">· {{ $booking->attendance->marked_at->format('d M Y, h:i A') }}</span>
                            @endif
                        </p>
                        @if ($booking->attendance->note)
                            <p class="mt-1 text-sm text-slate-600">{{ $booking->attendance->note }}</p>
                        @endif
                    @else
                        <p class="mt-1 text-sm text-slate-500">Not recorded yet.</p>
                    @endif

                    <form method="POST" action="{{ route('parent.bookings.attendance', $booking) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="attendance-status" class="label">Mark attendance</label>
                            <select id="attendance-status" name="status" class="input" required>
                                @foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $booking->attendance?->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="attendance-note" class="label">Note (optional)</label>
                            <input id="attendance-note" name="note" type="text" value="{{ old('note', $booking->attendance?->note) }}"
                                   maxlength="500" class="input" placeholder="e.g. Joined 10 minutes late">
                            @error('note') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-secondary btn-sm w-full">Save attendance</button>
                    </form>
                </div>

                {{-- Manage booking --}}
                @if ($booking->isCancellable())
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Manage this booking</h2>

                        <details class="group mt-4 rounded-lg border border-slate-200">
                            <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Edit the selected date, time or duration
                            </summary>
                            <form method="POST" action="{{ route('parent.bookings.reschedule', $booking) }}" class="space-y-3 border-t border-slate-200 p-4">
                                @csrf
                                <p class="text-xs leading-relaxed text-slate-600">
                                    You can update the class date, start time, and duration. The teacher&rsquo;s availability is checked before changes are saved, and your agreed booking price stays the same. Rescheduling is available more than {{ $rescheduleWindow }} hours before the class.
                                </p>
                                <div>
                                    <label for="start_at" class="label">Selected date &amp; time</label>
                                    <input id="start_at" name="start_at" type="datetime-local" required
                                           value="{{ old('start_at', $booking->start_at?->format('Y-m-d\TH:i')) }}"
                                           class="input">
                                    @error('start_at') <p class="error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="duration_minutes" class="label">Selected duration (minutes)</label>
                                    <input id="duration_minutes" name="duration_minutes" type="number" required
                                           min="15" max="600" step="15"
                                           value="{{ old('duration_minutes', $booking->duration_minutes) }}" class="input">
                                    @error('duration_minutes') <p class="error">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="btn-secondary btn-sm w-full">Request reschedule</button>
                            </form>
                        </details>

                        <details class="group mt-3 rounded-lg border border-rose-200">
                            <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                Cancel this booking
                            </summary>
                            <form method="POST" action="{{ route('parent.bookings.cancel', $booking) }}" class="space-y-3 border-t border-rose-200 p-4">
                                @csrf
                                <div>
                                    <label for="reason" class="label">Reason for cancellation <span class="text-rose-500">*</span></label>
                                    <textarea id="reason" name="reason" rows="3" required maxlength="500"
                                              class="input" placeholder="e.g. Teacher unavailable on that day">{{ old('reason') }}</textarea>
                                    @error('reason') <p class="error">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="btn-danger btn-sm w-full"
                                        onclick="return confirm('Cancel this booking? Any eligible amount will be refunded.');">
                                    Cancel booking
                                </button>
                            </form>
                        </details>
                    </div>
                @endif

                @if ($canMarkComplete)
                    <div class="card p-5">
                        <h2 class="text-base font-semibold text-slate-900">Session finished</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            The scheduled end time has passed. Confirm that the class took place so the teacher can be credited.
                        </p>
                        <form method="POST" action="{{ route('parent.bookings.complete', $booking) }}" class="mt-4"
                              onsubmit="return confirm('Mark this session as completed?');">
                            @csrf
                            <button type="submit" class="btn-success w-full">Mark as completed</button>
                        </form>
                    </div>
                @endif

                <div class="card p-5">
                    <h2 class="text-base font-semibold text-slate-900">Need help?</h2>
                    <p class="mt-1 text-sm text-slate-500">Raise a support ticket if something looks wrong with this booking.</p>
                    <a href="{{ route('support.create') }}" class="btn-secondary btn-sm mt-4 w-full">Contact support</a>
                </div>
            </div>
        </div>
    </div>
@endsection
