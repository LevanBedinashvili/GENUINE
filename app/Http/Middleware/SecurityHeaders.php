<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        $response->header('X-Frame-Options', 'DENY');

        $response->header('X-Content-Type-Options', 'nosniff');

        $response->header('X-XSS-Protection', '1; mode=block');

        $response->header('Content-Security-Policy', 
            "default-src 'self'; " .
            "script-src 'self' https://cdn.jsdelivr.net https://kit.fontawesome.com; " .
            "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
            "font-src 'self' https://fonts.gstatic.com https://kit-free.fontawesome.com; " .
            "img-src 'self' data: https:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';"
        );

        $response->header('Referrer-Policy', 'no-referrer-when-downgrade');

        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
