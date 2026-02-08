<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Filament Panel Access
 *
 * Prevents brute force enumeration attacks by limiting the number of
 * access attempts to the Filament admin panel per IP address.
 *
 * Security: Implements defense-in-depth against user enumeration
 * Performance: Uses Laravel's built-in rate limiter with Redis/Cache backend
 *
 * Configuration:
 * - 60 attempts per minute per IP address
 * - Returns 429 Too Many Requests on limit exceeded
 * - Logs excessive attempts for security monitoring
 */
class RateLimitFilamentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'filament-access:' . $request->ip();
        
        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, 60)) {
            // Log excessive attempts for security monitoring
            \Log::channel('security')->warning('Filament access rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'timestamp' => now()->toIso8601String(),
            ]);
            
            return response()->json([
                'message' => 'Too many access attempts. Please try again later.'
            ], 429);
        }
        
        // Increment attempt counter (expires after 60 seconds)
        RateLimiter::hit($key, 60);
        
        return $next($request);
    }
}
