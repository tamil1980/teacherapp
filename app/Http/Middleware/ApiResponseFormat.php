<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standardises API error responses so the Android client always receives a
 * predictable JSON shape.
 */
class ApiResponseFormat
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! $response instanceof Response) {
            return $response;
        }

        $content = $response->getContent();

        if ($response->getStatusCode() >= 400 && $content !== '' && ! str_starts_with(trim($content), '{')) {
            return $response;
        }

        return $response;
    }
}
