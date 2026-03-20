<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rate limit login attempts per IP address
 * 
 * SECURITY: Prevents brute force attacks on login endpoints
 * Default: 5 attempts per 15 minutes per IP
 */
class LoginRateLimit
{
    /**
     * Maximum login attempts allowed
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Time window in minutes
     */
    private const MINUTES = 15;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only rate limit login POST requests
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $ip = $request->ip();
        $key = 'login_attempts:' . $ip;

        // Get current attempt count
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Log::warning('Login rate limit exceeded', [
                'ip' => $ip,
                'attempts' => $attempts,
                'email' => $request->input('email'),
            ]);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . self::MINUTES . ' minutes.',
            ])->withInput($request->only('email'));
        }

        // Increment attempt counter
        Cache::put($key, $attempts + 1, now()->addMinutes(self::MINUTES));

        return $next($request);
    }
}
