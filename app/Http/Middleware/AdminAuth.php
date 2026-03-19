<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Verify that authenticated user has admin privileges
 * 
 * SECURITY: This middleware ensures only admin users can access protected routes
 * Prevents privilege escalation attacks where non-admin users attempt to access admin features
 */
class AdminAuth
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
        // User must be authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // User must have is_admin flag set to true
        if (!Auth::user()->is_admin) {
            // Log unauthorized access attempt
            \Illuminate\Support\Facades\Log::warning('Unauthorized admin access attempt', [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->view('errors.403', [
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
