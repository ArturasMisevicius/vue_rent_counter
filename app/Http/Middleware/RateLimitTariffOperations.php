<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Tariff Operations
 * 
 * Prevents abuse of tariff CRUD operations by limiting requests per user.
 * 
 * Limits:
 * - 60 requests per minute for authenticated users
 * - 10 requests per minute for IP-based (fallback)
 * 
 * Security: Prevents DoS attacks through excessive tariff operations
 */
class RateLimitTariffOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = auth()->check() ? 60 : 10;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            // Log rate limit violation for security monitoring
            logger()->warning('Tariff operation rate limit exceeded', [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'path' => $request->path(),
                'retry_after' => $seconds,
            ]);

            return response()->json([
                'message' => __('Too many requests. Please try again in :seconds seconds.', ['seconds' => $seconds]),
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
        ]);
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = auth()->user()) {
            return 'tariff-operations:user:' . $user->id;
        }

        return 'tariff-operations:ip:' . $request->ip();
    }
}
