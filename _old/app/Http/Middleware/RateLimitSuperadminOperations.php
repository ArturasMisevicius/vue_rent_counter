<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Superadmin Operations
 *
 * Implements comprehensive rate limiting for superadmin dashboard operations
 * to prevent abuse and ensure system stability. Different limits are applied
 * based on operation type and sensitivity.
 *
 * Security: Prevents abuse of privileged operations
 * Performance: Uses Laravel's built-in rate limiter with Redis backend
 *
 * Rate Limits:
 * - Dashboard API endpoints: 60 requests/minute per superadmin
 * - Bulk operations: 10 operations/minute per superadmin
 * - Export operations: 5 exports/minute per superadmin
 * - Password resets: 3 attempts/hour per user
 *
 * Requirements: Security considerations
 */
class RateLimitSuperadminOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $operationType  The type of operation (dashboard|bulk|export|password-reset)
     */
    public function handle(Request $request, Closure $next, string $operationType = 'dashboard'): Response
    {
        $user = $request->user();

        // Only apply rate limiting to authenticated superadmins
        if (! $user || ! $user->isSuperadmin()) {
            return $next($request);
        }

        $limits = $this->getRateLimits($operationType);
        $key = $this->getRateLimitKey($operationType, $user->id, $request);

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($key, $limits['max_attempts'])) {
            $this->logRateLimitExceeded($operationType, $user, $request);

            return response()->json([
                'message' => "Too many {$operationType} operations. Please try again later.",
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        // Increment attempt counter
        RateLimiter::hit($key, $limits['decay_seconds']);

        return $next($request);
    }

    /**
     * Get rate limit configuration for operation type.
     */
    private function getRateLimits(string $operationType): array
    {
        $configKey = match ($operationType) {
            'dashboard' => 'superadmin.rate_limits.dashboard',
            'bulk' => 'superadmin.rate_limits.bulk_operations',
            'export' => 'superadmin.rate_limits.exports',
            'password-reset' => 'superadmin.rate_limits.password_resets',
            default => null,
        };

        if ($configKey) {
            $config = config($configKey);

            return [
                'max_attempts' => $config['max_attempts'],
                'decay_seconds' => $config['decay_minutes'] * 60,
            ];
        }

        // Fallback for unknown operation types
        return [
            'max_attempts' => 30,
            'decay_seconds' => 60,
        ];
    }

    /**
     * Generate rate limit key for the operation.
     */
    private function getRateLimitKey(string $operationType, int $userId, Request $request): string
    {
        // For password resets, include target user to prevent abuse
        if ($operationType === 'password-reset' && $request->has('user_id')) {
            return "superadmin-{$operationType}:{$userId}:target-{$request->input('user_id')}";
        }

        return "superadmin-{$operationType}:{$userId}";
    }

    /**
     * Log rate limit exceeded events for security monitoring.
     *
     * @param  User  $user
     */
    private function logRateLimitExceeded(string $operationType, $user, Request $request): void
    {
        \Log::channel('security')->warning("Superadmin {$operationType} rate limit exceeded", [
            'operation_type' => $operationType,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
