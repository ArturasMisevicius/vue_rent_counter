<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * RateLimitTariffOperations
 * 
 * Rate limits tariff CRUD operations to prevent abuse.
 * 
 * Security:
 * - Limits tariff creates to 10 per hour per user
 * - Limits tariff updates to 20 per hour per user
 * - Limits tariff deletes to 5 per hour per user
 * - Prevents rapid-fire changes that could cause billing chaos
 * - Prevents accidental bulk deletions
 * - Mitigates compromised admin account abuse
 * 
 * Usage:
 * Apply to tariff routes via middleware alias 'rate.limit.tariff'
 * 
 * @package App\Http\Middleware
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
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $key = 'tariff-operations:' . $user->id;
        $limit = match ($request->method()) {
            'POST' => 10,    // 10 creates per hour
            'PUT', 'PATCH' => 20,    // 20 updates per hour
            'DELETE' => 5,    // 5 deletes per hour
            default => 100,  // 100 reads per hour
        };

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many tariff operations. Please try again later.',
                'retry_after' => $retryAfter,
                'retry_after_human' => gmdate('H:i:s', $retryAfter),
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour decay

        return $next($request);
    }
}

