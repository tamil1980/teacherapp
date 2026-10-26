<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function showLogin(Request $request): View
    {
        return view('auth.login', ['role' => $request->query('role', 'parent')]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'in:parent,teacher,admin'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->withInput();
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'This account has been suspended. Please contact support.']);
        }

        if (! empty($credentials['role']) && $user->role !== $credentials['role']) {
            return back()->withErrors(['email' => 'This account is registered as a '.ucfirst($user->role).'.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended($this->homeFor($user->role));
    }

    public function showRegister(Request $request): View
    {
        $role = $request->routeIs('register.teacher') ? 'teacher' : 'parent';

        return view('auth.register', ['role' => $role]);
    }

    public function register(Request $request): RedirectResponse
    {
        $role = $request->input('role', 'parent');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:parent,teacher'],
        ];

        if ($role === 'teacher') {
            $rules += [
                'headline' => ['nullable', 'string', 'max:180'],
                'qualification_summary' => ['nullable', 'string', 'max:180'],
                'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'pincode' => ['nullable', 'string', 'regex:/^[1-9]\d{5}$/'],
                'teaching_modes' => ['nullable', 'array'],
                'teaching_modes.*' => ['in:online,offline'],
            ];
        } else {
            $rules += [
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'max:100'],
                'pincode' => ['nullable', 'string', 'regex:/^[1-9]\d{5}$/'],
            ];
        }

        $validated = $request->validate($rules, [
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Passwords do not match. Please enter the same password in both fields.',
        ]);

        $user = $role === 'teacher'
            ? $this->registration->registerTeacher($validated)
            : $this->registration->registerParent($validated);

        if ($role === 'teacher' && ! empty($validated['teaching_modes'])) {
            $teacher = $user->teacherProfile;
            $teacher->update([
                'accepts_online' => in_array('online', $validated['teaching_modes'], true),
                'accepts_offline' => in_array('offline', $validated['teaching_modes'], true),
            ]);

            foreach ($validated['teaching_modes'] as $mode) {
                $teacher->teachingModes()->firstOrCreate(['mode' => $mode]);
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect($this->homeFor($role))
            ->with('success', $role === 'teacher'
                ? 'Welcome to '.($platformName ?? 'EduBook').'! Complete your profile and wait for admin verification before you appear in search.'
                : 'Welcome to '.($platformName ?? 'EduBook').'! Add your child profile to start booking classes.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'teacher' => route('teacher.dashboard'),
            'admin' => route('admin.dashboard'),
            default => route('parent.dashboard'),
        };
    }
}
