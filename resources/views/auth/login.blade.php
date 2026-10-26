@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    @php
        $role = in_array($role, ['parent', 'teacher', 'admin'], true) ? $role : 'parent';
    @endphp

    <div class="mx-auto flex max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="page-title">Welcome back</h1>
            <p class="page-subtitle">Log in to manage your bookings and classes.</p>
        </div>

        <div class="card mt-8 p-6">
            <div class="mb-6 grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1" aria-label="Choose account type">
                <a href="{{ route('login', ['role' => 'parent']) }}"
                   @if ($role === 'parent') aria-current="page" @endif
                   class="rounded-md px-2 py-2 text-center text-xs font-semibold transition sm:text-sm {{ $role === 'parent' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Parent
                </a>
                <a href="{{ route('login', ['role' => 'teacher']) }}"
                   @if ($role === 'teacher') aria-current="page" @endif
                   class="rounded-md px-2 py-2 text-center text-xs font-semibold transition sm:text-sm {{ $role === 'teacher' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Teacher
                </a>
                <a href="{{ route('login', ['role' => 'admin']) }}"
                   @if ($role === 'admin') aria-current="page" @endif
                   class="rounded-md px-2 py-2 text-center text-xs font-semibold transition sm:text-sm {{ $role === 'admin' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Admin
                </a>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="role" value="{{ $role }}">

                <div>
                    <label for="email" class="label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="input" autocomplete="email">
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" required class="input" autocomplete="current-password">
                    @error('password') <p class="error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary w-full">Log in as {{ $role === 'parent' ? 'parent' : $role }}</button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-slate-500">
            New to {{ $platformName ?? 'EduBook' }}?
            <a href="{{ route('register.parent') }}" class="font-semibold text-brand-600 hover:underline">Create a parent account</a>
            or
            <a href="{{ route('register.teacher') }}" class="font-semibold text-brand-600 hover:underline">register as a teacher</a>.
        </p>

        @if (app()->environment('local'))
        <div class="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-4 text-xs text-slate-500">
            <p class="font-semibold text-slate-700">Demo accounts</p>
            <ul class="mt-2 space-y-1">
                <li>Parent — <code>parent@edubook.test</code> / <code>parent@1234</code></li>
                <li>Teacher — <code>lakshmi@edubook.test</code> / <code>teacher@1234</code></li>
                <li>Admin — <code>admin@edubook.test</code> / <code>admin@1234</code></li>
            </ul>
        </div>
        @endif
    </div>
@endsection
