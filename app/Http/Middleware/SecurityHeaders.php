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
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://kit.fontawesome.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdn.web-fonts.ge https://cdnjs.cloudflare.com; " .
            "font-src 'self' https://fonts.gstatic.com https://kit-free.fontawesome.com https://cdn.web-fonts.ge https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: https:; " .
            "media-src 'self'; " .
            "connect-src 'self' https://www.google.com/recaptcha/; " .
            "frame-src https://www.google.com/recaptcha/ https://payment.bog.ge https://payment.sandbox.bog.ge; " .
            "frame-ancestors 'none';"
        );

        $response->header('Referrer-Policy', 'no-referrer-when-downgrade');

        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
