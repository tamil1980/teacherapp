<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends ApiController
{
    /**
     * Sign in with an email address or a mobile number.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
        $user = User::where($field, $validated['login'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('These credentials do not match our records.', 422);
        }

        if ($user->status !== 'active') {
            return $this->error('Your account has been suspended. Please contact support.', 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $token = $user->createToken($validated['device_name'] ?? 'mobile')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], 'Signed in successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(['user' => $this->userPayload($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'mobile' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'mobile')->ignore($user->id)],
        ]);

        $user->update($validated);

        return $this->ok(['user' => $this->userPayload($user->fresh())], 'Profile updated.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok(null, 'Signed out.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        $user->loadMissing(['teacherProfile', 'parentProfile']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role' => $user->role,
            'status' => $user->status,
            'avatar_path' => $user->avatar_path,
            'created_at' => optional($user->created_at)->toIso8601String(),
            'teacher_profile' => $user->teacherProfile ? [
                'id' => $user->teacherProfile->id,
                'slug' => $user->teacherProfile->slug,
                'headline' => $user->teacherProfile->headline,
                'verification_status' => $user->teacherProfile->verification_status,
                'rating_average' => (float) $user->teacherProfile->rating_average,
                'rating_count' => (int) $user->teacherProfile->rating_count,
                'city' => $user->teacherProfile->city,
                'photo_path' => $user->teacherProfile->photo_path,
            ] : null,
            'parent_profile' => $user->parentProfile ? [
                'id' => $user->parentProfile->id,
                'city' => $user->parentProfile->city,
                'state' => $user->parentProfile->state,
            ] : null,
        ];
    }
}
