@php
    $rate = $lowestRate ?? null;
    $avgRating = (float) ($teacher->rating_average ?? 0);
    $reviewCount = (int) ($teacher->rating_count ?? 0);
    $photo = $teacher->photo_path ? asset('storage/'.$teacher->photo_path) : null;
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacher->user?->name ?? 'T', 0, 1));
@endphp

<article class="card flex h-full flex-col overflow-hidden transition hover:shadow-md">
    <div class="flex items-start gap-4 border-b border-slate-100 p-5">
        @if ($photo)
            <img src="{{ $photo }}" alt="{{ $teacher->user?->name }}" class="h-16 w-16 shrink-0 rounded-full object-cover ring-2 ring-brand-100">
        @else
            <span role="img" aria-label="{{ $teacher->user?->name }} profile placeholder" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xl font-bold text-brand-700 ring-2 ring-brand-100">{{ $initial }}</span>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate text-base font-bold text-slate-900">
                    <a href="{{ route('teachers.show', $teacher) }}" class="hover:text-brand-600">{{ $teacher->user?->name }}</a>
                </h3>
                @if ($teacher->verification_status === 'approved')
                    <span class="badge bg-emerald-100 text-emerald-700" title="Identity and qualifications verified">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 000 4h1v1.5A4.5 4.5 0 0010.5 19h-1A4.5 4.5 0 0115 14.5V13h1a2 2 0 000-4h-.5V5.5A4.5 4.5 0 0010 1zm3.75 8.75V5.5a3.75 3.75 0 10-7.5 0v4.25a1 1 0 00-1 1v4a1 1 0 001 1h.75v1.5a3.25 3.25 0 003.25 3.25h1A3.25 3.25 0 0015 17.5V13h.75a1 1 0 001-1v-1.25a1 1 0 00-.25-.62z" clip-rule="evenodd"/></svg>
                        Verified
                    </span>
                @endif
            </div>

            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $teacher->headline }}</p>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                @if ($reviewCount > 0)
                    <span class="inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 00.95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.58a1 1 0 00-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 00-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 00-.36-1.12L1.74 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 00.95-.69L9.05 2.93z"/></svg>
                        <span class="font-semibold text-slate-700">{{ number_format($avgRating, 1) }}</span>
                        <span>({{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }})</span>
                    </span>
                @else
                    <span class="text-slate-500">No reviews yet</span>
                @endif
                <span>{{ $teacher->experience_years }} yr{{ $teacher->experience_years == 1 ? '' : 's' }} exp.</span>
                @if ($teacher->city)
                    <span>• {{ $teacher->city }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex-1 p-5">
        <div class="flex flex-wrap gap-1.5">
            @foreach ($teacher->teacherSubjects->take(4) as $teacherSubject)
                <a href="{{ route('subjects.show', $teacherSubject->subject->slug) }}"
                   class="badge bg-brand-50 text-brand-700 hover:bg-brand-100">{{ $teacherSubject->subject->icon }} {{ $teacherSubject->subject->name }}</a>
            @endforeach
            @if ($teacher->teacherSubjects->count() > 4)
                <span class="badge bg-slate-100 text-slate-600">+{{ $teacher->teacherSubjects->count() - 4 }} more</span>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap gap-1.5">
            @foreach ($teacher->teacherBoards->take(3) as $teacherBoard)
                <span class="badge bg-slate-100 text-slate-600">{{ $teacherBoard->board->name }}</span>
            @endforeach
            @if ($teacher->teacherBoards->count() > 3)
                <span class="badge bg-slate-100 text-slate-600">+{{ $teacher->teacherBoards->count() - 3 }}</span>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-5 py-3">
        <div>
            @if ($rate !== null)
                <span class="text-lg font-extrabold text-slate-900">₹{{ number_format($rate, 0) }}</span>
                <span class="text-xs text-slate-500">/ hour</span>
            @else
                <span class="text-xs text-slate-400">Fee on request</span>
            @endif
        </div>

        <div class="flex items-center gap-1.5">
            @if ($teacher->accepts_online)
                <span class="badge bg-sky-50 text-sky-700" title="Online classes">Online</span>
            @endif
            @if ($teacher->accepts_offline)
                <span class="badge bg-violet-50 text-violet-700" title="Offline / home tuition">Offline</span>
            @endif
        </div>
    </div>

    <div class="border-t border-slate-100 px-5 py-3">
        <a href="{{ route('teachers.show', $teacher) }}" class="btn-secondary w-full">View profile & availability</a>
    </div>
</article>