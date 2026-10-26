<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to the given roles.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        $allowed = $roles;

        if (! in_array($user->role, $allowed, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to access this resource.',
                    'required_roles' => $allowed,
                ], 403);
            }

            abort(403, 'You do not have permission to access this page.');
        }

        if ($user->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been suspended. Please contact support.'], 403);
            }

            abort(403, 'Your account has been suspended. Please contact support.');
        }

        return $next($request);
    }
}
