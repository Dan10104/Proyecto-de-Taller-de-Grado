<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivateResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'same-origin');
        }
        $response->headers->set('X-Frame-Options', 'DENY');
        // Navigation/embedding protection without blocking Inertia, local Vite or camera blobs.
        // This deliberately is not a script-src policy or a substitute for output escaping.
        $response->headers->set('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'none'");
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
