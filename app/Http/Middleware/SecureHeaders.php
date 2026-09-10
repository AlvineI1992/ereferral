<?php

namespace App\Http\Middleware;

use Closure;

class SecureHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Remove headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('server');

        // Do not apply restrictive production policies while Vite development
        // mode is active. This also protects local setups whose APP_ENV was
        // temporarily left as "production".
        if (! app()->environment('production') || is_file(public_path('hot'))) {
            return $response;
        }

        // Browser and transport hardening for production responses.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "style-src-elem 'self' 'unsafe-inline' https://fonts.bunny.net",
            "script-src 'self' 'unsafe-inline'",
            "script-src-elem 'self' 'unsafe-inline'",
            "connect-src 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $directives));

        return $response;
    }
}
