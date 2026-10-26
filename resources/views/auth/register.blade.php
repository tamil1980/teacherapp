@extends('layouts.app')

@section('title', ($role === 'teacher') ? 'Register as a teacher' : 'Create a parent account')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="page-title">{{ ($role === 'teacher') ? 'Join as a teacher' : 'Create your parent account' }}</h1>
            <p class="page-subtitle">
                {{ ($role === 'teacher')
                    ? 'Set your own hourly fees, subjects and availability — you are in control.'
                    : 'Add your child profile, find verified teachers and book classes in minutes.' }}
            </p>
        </div>

        <div class="card mt-8 p-6 sm:p-8">
            <div class="mb-6 grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-1">
                <a href="{{ route('register.parent') }}"
                   class="rounded-md px-3 py-2 text-center text-sm font-semibold transition {{ ($role === 'teacher') ? 'text-slate-500 hover:text-slate-700' : 'bg-white text-brand-700 shadow-sm' }}">
                    Parent / Student
                </a>
                <a href="{{ route('register.teacher') }}"
                   class="rounded-md px-3 py-2 text-center text-sm font-semibold transition {{ ($role === 'teacher') ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                    Teacher
                </a>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="role" value="{{ ($role === 'teacher') ? 'teacher' : 'parent' }}">

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Full name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="input" autocomplete="name">
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="mobile" class="label">Mobile number</label>
                        <input id="mobile" name="mobile" type="tel" value="{{ old('mobile') }}" required inputmode="numeric" maxlength="10" placeholder="98XXXXXXXX" class="input">
                        <p class="hint">10-digit Indian mobile number.</p>
                        @error('mobile') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="email" class="label">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input" autocomplete="email">
                        @error('email') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <input id="password" name="password" type="password" required minlength="8" class="input" autocomplete="new-password">
                        <p class="hint">Use at least 8 characters.</p>
                        @error('password') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                               class="input" autocomplete="new-password" aria-describedby="password-confirmation-hint">
                        <p id="password-confirmation-hint" class="hint">Enter the same password again.</p>
                        <p class="error hidden" data-password-mismatch aria-live="polite">Passwords do not match.</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="city" class="label">City</label>
                        <input id="city" name="city" type="text" value="{{ old('city') }}" class="input">
                        @error('city') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="state" class="label">State</label>
                        <input id="state" name="state" type="text" value="{{ old('state') }}" class="input">
                        @error('state') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pincode" class="label">PIN code</label>
                        <input id="pincode" name="pincode" type="text" value="{{ old('pincode') }}" inputmode="numeric" maxlength="6" class="input">
                        @error('pincode') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if (($role === 'teacher'))
                    <div>
                        <label for="headline" class="label">Professional headline</label>
                        <input id="headline" name="headline" type="text" value="{{ old('headline') }}" placeholder="e.g. Physics &amp; Mathematics Faculty — 12 years in CBSE" class="input">
                        @error('headline') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="qualification_summary" class="label">Highest qualification</label>
                            <input id="qualification_summary" name="qualification_summary" type="text" value="{{ old('qualification_summary') }}" placeholder="M.Sc. Physics, B.Ed." class="input">
                            @error('qualification_summary') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="experience_years" class="label">Teaching experience (years)</label>
                            <input id="experience_years" name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years', 0) }}" class="input">
                            @error('experience_years') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <fieldset>
                        <legend class="label">Teaching modes you offer</legend>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="teaching_modes[]" value="online" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(in_array('online', old('teaching_modes', [])))>
                                Online classes
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="teaching_modes[]" value="offline" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(in_array('offline', old('teaching_modes', [])))>
                                Offline / home tuition
                            </label>
                        </div>
                        @error('teaching_modes') <p class="error">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">Create account</button>
                    <p class="text-sm text-slate-500">
                        Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Log in</a>
                    </p>
                </div>
            </form>
        </div>

        @if (($role === 'teacher'))
            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Verification required</p>
                <p class="mt-1">
                    Teachers must upload identity and qualification documents before an administrator can approve their profile.
                    Only approved teachers appear in public search results.
                </p>
            </div>
        @endif
    </div>
@endsection
