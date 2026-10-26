@extends('layouts.app')

@section('title', 'My Reviews')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">My reviews</h1>
                <p class="page-subtitle">Feedback you have left for your teachers.</p>
            </div>
            <a href="{{ route('parent.bookings.index', ['status' => 'completed']) }}" class="btn-secondary">Review completed sessions</a>
        </div>

        <div class="mt-6">
            @if ($reviews->isEmpty())
                <div class="card px-6 py-16 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">No reviews yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        Once a session is completed you can rate your teacher and help other parents choose.
                    </p>
                    <a href="{{ route('parent.bookings.index', ['status' => 'completed']) }}" class="btn-primary mt-6">View completed sessions</a>
                </div>
            @else
                <div class="space-y-5">
                    @foreach ($reviews as $review)
                        <div class="card p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-amber-600">
                                        {{ str_repeat('★', (int) $review->rating) }}
                                        <span class="text-slate-200">{{ str_repeat('★', max(0, 5 - (int) $review->rating)) }}</span>
                                        <span class="ml-1 font-normal text-slate-500">{{ $review->rating }} / 5</span>
                                    </p>
                                    <p class="mt-1.5 text-sm font-semibold text-slate-900">
                                        {{ $review->teacher?->user?->name ?? 'Teacher' }}
                                    </p>
                                    @if ($review->booking)
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Booking
                                            <a href="{{ route('parent.bookings.show', $review->booking) }}" class="font-mono hover:text-brand-600 hover:underline">
                                                {{ $review->booking->reference_no }}
                                            </a>
                                            @if ($review->booking->subject)
                                                · {{ $review->booking->subject->name }}
                                            @endif
                                            @if ($review->booking->start_at)
                                                · {{ $review->booking->start_at->format('d M Y') }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    @if ($review->is_anonymous)
                                        <span class="badge bg-slate-100 text-slate-600">Anonymous</span>
                                    @endif
                                    <span class="text-xs text-slate-400">{{ $review->created_at?->format('d M Y') }}</span>
                                </div>
                            </div>

                            @if ($review->title)
                                <p class="mt-3 text-sm font-semibold text-slate-900">{{ $review->title }}</p>
                            @endif

                            @if ($review->comment)
                                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->comment }}</p>
                            @else
                                <p class="mt-2 text-sm text-slate-400">No comment provided.</p>
                            @endif

                            @if ($review->teacher_reply)
                                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-900">
                                        Reply from {{ $review->teacher?->user?->name ?? 'the teacher' }}
                                        @if ($review->replied_at)
                                            <span class="ml-1 text-xs font-normal text-slate-500">{{ $review->replied_at->format('d M Y, h:i A') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->teacher_reply }}</p>
                                </div>
                            @endif

                            <details class="group mt-4 rounded-lg border border-slate-200">
                                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    Edit this review
                                </summary>
                                <form method="POST" action="{{ route('parent.reviews.update', $review) }}" class="space-y-4 border-t border-slate-200 p-4">
                                    @csrf

                                    <fieldset>
                                        <legend class="label">Rating <span class="text-rose-500">*</span></legend>
                                        <div class="flex flex-wrap gap-4">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="radio" name="rating" value="{{ $i }}" required
                                                           @checked((int) old('rating', $review->rating) === $i)
                                                           class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                    {{ $i }} star{{ $i === 1 ? '' : 's' }}
                                                </label>
                                            @endfor
                                        </div>
                                        @error('rating') <p class="error">{{ $message }}</p> @enderror
                                    </fieldset>

                                    <div>
                                        <label for="review-title-{{ $review->id }}" class="label">Headline</label>
                                        <input id="review-title-{{ $review->id }}" name="title" type="text" maxlength="120"
                                               value="{{ old('title', $review->title) }}" class="input"
                                               placeholder="e.g. Excellent concept teaching">
                                        @error('title') <p class="error">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="review-comment-{{ $review->id }}" class="label">Your review</label>
                                        <textarea id="review-comment-{{ $review->id }}" name="comment" rows="4" maxlength="2000"
                                                  class="input" placeholder="Update your feedback…">{{ old('comment', $review->comment) }}</textarea>
                                        @error('comment') <p class="error">{{ $message }}</p> @enderror
                                    </div>

                                    <button type="submit" class="btn-primary btn-sm">Save changes</button>
                                </form>
                            </details>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
