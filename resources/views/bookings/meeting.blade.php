@extends('layouts.app')

@section('title', 'Class Meeting')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="page-title">Online Class Meeting</h1>
    <p class="page-subtitle">Booking {{ $booking->reference_no }}</p>

    <div class="mt-6 card p-6">
        @if($session && $session->join_url)
            <p class="text-sm text-slate-600">Meeting link for your class with {{ $booking->teacher->user->name }}</p>
            <a href="{{ $session->join_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary mt-4 w-full">Join Meeting</a>
            @if($session->meeting_id)
                <p class="mt-3 text-xs text-slate-500">Meeting ID: {{ $session->meeting_id }}</p>
            @endif
            @if($session->passcode)
                <p class="text-xs text-slate-500">Passcode: {{ $session->passcode }}</p>
            @endif
        @else
            <p class="text-sm text-slate-600">Meeting link will be shared by the teacher once the class is confirmed.</p>
        @endif
    </div>
</div>
@endsection
