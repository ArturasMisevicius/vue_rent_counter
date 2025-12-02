<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Subscription Checks
 * 
 * Prevents DoS attacks by limiting the number of subscription check requests.
 * 
 * Limits:
 * - Authenticated users: 60 requests per minute
 * - Unauthenticated (IP-based): 10 requests per minute
 * 
 * Security: Violations are automatically logged for monitoring
 * 
 * @package App\Http\Middleware
 */
final class RateLimitSubscriptionChecks
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts($request))) {
            $this->logViolation($request, $key);
            return $this->buildRateLimitResponse($key);
        }
        
        RateLimiter::hit($key, $this->decayMinutes() * 60);
        
        return $next($request);
    }
    
    /**
     * Resolve the request signature for rate limiting.
     *
     * Uses user ID for authenticated requests, IP address for unauthenticated.
     *
     * @param  Request  $request
     * @return string
     */
    private function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sprintf('subscription-check:user:%d', $user->id);
        }
        
        return sprintf('subscription-check:ip:%s', $request->ip());
    }
    
    /**
     * Get maximum attempts allowed.
     *
     * @param  Request  $request
     * @return int
     */
    private function maxAttempts(Request $request): int
    {
        return $request->user() 
            ? config('subscription.rate_limit.authenticated', 60)
            : config('subscription.rate_limit.unauthenticated', 10);
    }
    
    /**
     * Get decay time in minutes.
     *
     * @return int
     */
    private function decayMinutes(): int
    {
        return 1;
    }
    
    /**
     * Log rate limit violation for security monitoring.
     *
     * @param  Request  $request
     * @param  string  $key
     * @return void
     */
    private function logViolation(Request $request, string $key): void
    {
        Log::channel('security')->warning('Rate limit exceeded for subscription checks', [
            'key' => $key,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Build rate limit response.
     *
     * @param  string  $key
     * @return Response
     */
    private function buildRateLimitResponse(string $key): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        return response()->json([
            'message' => 'Too many subscription check attempts. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)
            ->header('Retry-After', (string) $retryAfter)
            ->header('X-RateLimit-Limit', (string) $this->maxAttempts(request()))
            ->header('X-RateLimit-Remaining', '0');
    }
}
