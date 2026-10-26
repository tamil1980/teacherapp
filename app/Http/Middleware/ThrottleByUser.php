<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simple per-user/IP rate limiter for sensitive endpoints.
 */
class ThrottleByUser
{
    public function handle(Request $request, Closure $next, string $key = 'default', int $maxAttempts = 60, int $decaySeconds = 60): Response
    {
        $limiterKey = $key.':'.($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Too many requests. Please retry in {$seconds} second(s).",
                ], 429);
            }

            abort(429, "Too many requests. Please retry in {$seconds} second(s).");
        }

        RateLimiter::hit($limiterKey, $decaySeconds);

        return $next($request);
    }
}
