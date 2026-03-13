<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Validation Operations
 * 
 * Provides granular rate limiting for different validation operations
 * to prevent abuse and DoS attacks on expensive validation endpoints.
 * 
 * SECURITY FEATURES:
 * - Operation-specific rate limits
 * - User and IP-based limiting
 * - Comprehensive audit logging
 * - Graceful degradation
 * 
 * @package App\Http\Middleware
 */
class RateLimitValidationOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $operation = $this->determineOperation($request);
        $key = $this->resolveRequestSignature($request, $operation);
        
        // Get operation-specific limits
        $limits = config('security.rate_limiting.limits', [
            'single_validation' => 60, // per minute
            'batch_validation' => 10,  // per minute
            'rate_change_validation' => 5, // per minute
            'estimated_reading_validation' => 30, // per minute
        ]);

        $limit = $limits[$operation] ?? 30;
        $window = 60; // 1 minute in seconds

        // Check rate limit
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $this->logRateLimitExceeded($request, $operation, $limit);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "Too many {$operation} requests. Please try again later.",
                'retry_after' => RateLimiter::availableIn($key),
                'limit' => $limit,
                'window' => $window,
            ], 429, [
                'Retry-After' => RateLimiter::availableIn($key),
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => max(0, $limit - RateLimiter::attempts($key)),
            ]);
        }

        // Increment rate limit counter
        RateLimiter::hit($key, $window);

        $response = $next($request);

        // Add rate limit headers to response
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - RateLimiter::attempts($key)));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($window)->timestamp);

        return $response;
    }

    /**
     * Determine the operation type based on request characteristics.
     */
    private function determineOperation(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();
        
        // Check for batch operations
        if (str_contains($path, 'batch') || 
            ($request->has('readings') && is_array($request->input('readings')))) {
            return 'batch_validation';
        }
        
        // Check for rate change operations
        if (str_contains($path, 'rate-change') || 
            str_contains($path, 'rate_change') ||
            $request->has('rate_schedule')) {
            return 'rate_change_validation';
        }
        
        // Check for estimated reading operations
        if (str_contains($path, 'estimated') || 
            $request->has('estimated_reading')) {
            return 'estimated_reading_validation';
        }
        
        // Default to single validation
        return 'single_validation';
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    private function resolveRequestSignature(Request $request, string $operation): string
    {
        $user = $request->user();
        
        // Use user ID if authenticated, otherwise fall back to IP
        $identifier = $user ? "user:{$user->id}" : "ip:" . $request->ip();
        
        // Include tenant context for multi-tenant isolation
        $tenantId = $user?->tenant_id ?? 'guest';
        
        return "validation_rate_limit:{$operation}:{$tenantId}:{$identifier}";
    }

    /**
     * Log rate limit exceeded events for security monitoring.
     */
    private function logRateLimitExceeded(Request $request, string $operation, int $limit): void
    {
        $user = $request->user();
        
        Log::warning('Rate limit exceeded for validation operation', [
            'operation' => $operation,
            'limit' => $limit,
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString(),
        ]);

        // Dispatch security event for centralized monitoring
        if (class_exists(\App\Events\SecurityViolationDetected::class)) {
            \App\Events\SecurityViolationDetected::dispatch(
                violationType: 'rate_limit_exceeded',
                originalInput: $operation,
                sanitizedAttempt: $operation,
                ipAddress: hash('sha256', $request->ip() . config('app.key')),
                userId: $user?->id,
                context: [
                    'operation' => $operation,
                    'limit' => $limit,
                    'path' => $request->path(),
                    'method' => $request->method(),
                ]
            );
        }
    }
}