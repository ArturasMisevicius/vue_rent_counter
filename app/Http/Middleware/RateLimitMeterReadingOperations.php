<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * RateLimitMeterReadingOperations
 * 
 * Rate limits meter reading operations to prevent abuse.
 * 
 * Security:
 * - Limits meter reading updates to 20 per hour per user
 * - Prevents DoS attacks through rapid updates
 * - Protects invoice recalculation system from overload
 * - Reduces audit log flooding
 * - Prevents database performance degradation
 * 
 * Configuration:
 * - Default limit: 20 updates per hour
 * - Configurable via config/billing.php
 * - Uses Redis/cache for distributed rate limiting
 * 
 * @package App\Http\Middleware
 */
class RateLimitMeterReadingOperations
{
    /**
     * Handle an incoming request.
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip rate limiting for unauthenticated requests
        // (they will be caught by auth middleware)
        if (!$user) {
            return $next($request);
        }

        // Rate limit key per user
        $key = 'meter-reading-operations:' . $user->id;
        
        // Get limit from config or use default
        $limit = config('billing.rate_limits.meter_reading_updates', 20);

        // Check if user has exceeded rate limit
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            // Log rate limit violation for security monitoring
            \Illuminate\Support\Facades\Log::warning('Meter reading rate limit exceeded', [
                'user_id' => $user->id,
                'user_role' => $user->role->value,
                'ip_address' => $request->ip(),
                'retry_after' => $retryAfter,
            ]);
            
            // Return 429 Too Many Requests with retry information
            return response()->json([
                'message' => __('meter_readings.rate_limit_exceeded'),
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Increment rate limit counter (1 hour decay)
        RateLimiter::hit($key, 3600);

        return $next($request);
    }
}
