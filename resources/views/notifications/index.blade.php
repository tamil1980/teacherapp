@extends('layouts.app')

@section('title', 'Notifications — EduBook')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="page-title">Notifications</h1>
                <p class="page-subtitle">
                    @if ($unreadCount > 0)
                        <span class="badge bg-brand-100 text-brand-700">{{ $unreadCount }} unread</span>
                    @else
                        You are all caught up.
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PUT')
                <button type="submit" class="btn-secondary btn-sm">Mark all as read</button>
            </form>
        </div>

        @if ($notifications->isEmpty())
            <div class="card mt-8 p-12 text-center">
                <div class="text-4xl">🔔</div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">No notifications yet</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Booking updates, support replies and platform announcements will show up here.</p>
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($notifications as $notification)
                    <div class="card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900">{{ $notification->title }}</h2>
                                <p class="mt-1 text-sm text-slate-600">{{ $notification->message }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-400">{{ $notification->created_at->format('d M Y, h:i A') }}</span>
                                @if ($notification->read_at)
                                    <span class="badge bg-slate-100 text-slate-600">Read</span>
                                @else
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-sm">Mark read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
