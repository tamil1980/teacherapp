<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the signed-in user has completed the role specific onboarding
 * (e.g. a teacher who still has to finish their profile).
 */
class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next, string $role = 'teacher'): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($role === 'teacher') {
            $teacher = $user->teacherProfile;

            if (! $teacher || ! $teacher->teacherSubjects()->exists() || ! $teacher->fees()->where('is_active', true)->exists()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Complete your teacher profile before continuing.',
                        'redirect' => route('teacher.profile.edit'),
                    ], 428);
                }

                return redirect()->route('teacher.profile.edit')
                    ->with('warning', 'Please complete your teacher profile before continuing.');
            }
        }

        return $next($request);
    }
}
