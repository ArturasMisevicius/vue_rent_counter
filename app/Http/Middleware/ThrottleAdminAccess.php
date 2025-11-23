<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttle admin panel access attempts to prevent brute force attacks.
 *
 * This middleware implements rate limiting for admin panel access,
 * tracking failed authorization attempts per IP address.
 *
 * Security Features:
 * - 10 attempts per 5 minutes per IP
 * - Only counts failed attempts (403 responses)
 * - Clears counter on successful access
 * - Returns 429 Too Many Requests when limit exceeded
 *
 * @see \App\Http\Middleware\EnsureUserIsAdminOrManager
 */
final class ThrottleAdminAccess
{
    /**
     * Maximum attempts allowed per time window.
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * Time window in seconds (5 minutes).
     */
    private const DECAY_SECONDS = 300;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return $this->buildRateLimitResponse($key);
        }

        $response = $next($request);

        // Only count failed authorization attempts
        if ($response->status() === 403) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        } elseif ($response->status() === 200) {
            // Clear rate limit on successful access
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * Resolve the rate limit key for the request.
     */
    private function resolveRequestSignature(Request $request): string
    {
        return 'admin-access:' . $request->ip();
    }

    /**
     * Build the rate limit exceeded response.
     */
    private function buildRateLimitResponse(string $key): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'message' => __('app.auth.too_many_attempts'),
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }
}
