<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use DateTime;

/**
 * Rate limiting middleware for shop payment validation
 * 
 * SECURITY:
 * - Limits payment validation attempts per IP address
 * - Blocks IPs that exceed threshold for 24 hours
 * - Uses cache instead of hardcoded blocklists
 * - Prevents brute force attacks on shop validation endpoint
 * 
 * Configuration:
 * - MAX_ATTEMPTS: 5 attempts per 60 seconds
 * - BAN_DURATION: 24 hours (86400 seconds)
 */
class ShopRateLimit
{
    /**
     * Maximum validation attempts per time window
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Time window in seconds (60 seconds)
     */
    private const TIME_WINDOW = 60;

    /**
     * Ban duration in seconds (24 hours)
     */
    private const BAN_DURATION = 86400;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $endpointKey = 'shop_validation:' . $request->path();
        
        // Check if IP is currently banned
        $banKey = 'shop_ban:' . $ip;
        if (Cache::has($banKey)) {
            $bannedUntil = Cache::get($banKey);
            
            Log::warning('Blocked request from banned IP', [
                'ip' => $ip,
                'endpoint' => $request->path(),
                'banned_until' => $bannedUntil,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Your IP address has been temporarily blocked due to too many requests. Please try again later.',
            ], 429);
        }

        // Get attempt count for this IP
        $attemptKey = $endpointKey . ':' . $ip;
        $attempts = Cache::get($attemptKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            // Ban this IP for 24 hours
            Cache::put($banKey, now()->addSeconds(self::BAN_DURATION)->toDateTimeString(), self::BAN_DURATION);
            
            Log::warning('IP address banned for excessive requests', [
                'ip' => $ip,
                'endpoint' => $request->path(),
                'attempts' => $attempts,
                'ban_duration_hours' => self::BAN_DURATION / 3600,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many validation attempts. Your IP has been blocked for 24 hours.',
            ], 429);
        }

        // Increment attempt counter
        Cache::put($attemptKey, $attempts + 1, now()->addSeconds(self::TIME_WINDOW));

        if ($attempts > 0) {
            Log::info('Shop validation attempt recorded', [
                'ip' => $ip,
                'endpoint' => $request->path(),
                'attempt' => $attempts + 1,
                'max_attempts' => self::MAX_ATTEMPTS,
            ]);
        }

        return $next($request);
    }
}
