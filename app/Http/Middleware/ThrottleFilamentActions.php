<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttle Filament Actions Middleware
 *
 * Implements rate limiting for Filament admin panel actions to prevent abuse.
 * Uses Laravel's RateLimiter with per-user, per-IP, per-path signatures.
 *
 * ## Configuration
 * - Max attempts: 60 per minute (configurable via THROTTLE_REQUESTS)
 * - Decay time: 60 seconds (configurable via THROTTLE_DECAY_MINUTES)
 * - Signature: user_id|ip|path (prevents bypass via multiple IPs)
 *
 * ## Use Cases
 * - Prevent tenant management abuse
 * - Protect bulk operations
 * - Mitigate DoS attacks
 * - Reduce notification spam
 *
 * ## Response
 * - 429 Too Many Requests when limit exceeded
 * - Retry-After header with seconds until reset
 * - JSON response for API requests
 *
 * @see \Illuminate\Cache\RateLimiter
 */
final class ThrottleFilamentActions
{
    /**
     * Create a new middleware instance.
     *
     * @param  RateLimiter  $limiter  Laravel's rate limiter instance
     */
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @return Response The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip rate limiting for GET requests (read-only)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) config('throttle.requests', 60);
        $decayMinutes = (int) config('throttle.decay_minutes', 1);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return $this->buildResponse($request, $retryAfter);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve the request signature for rate limiting.
     *
     * Creates a unique signature combining user ID, IP address, and path
     * to prevent bypass attempts via multiple IPs or accounts.
     *
     * @param  Request  $request  The incoming request
     * @return string SHA-1 hash of the signature
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $ip = $request->ip();
        $path = $request->path();

        return sha1("{$userId}|{$ip}|{$path}");
    }

    /**
     * Calculate remaining attempts for the current request.
     *
     * @param  string  $key  The rate limit key
     * @param  int  $maxAttempts  Maximum allowed attempts
     * @return int Remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }

    /**
     * Build the rate limit exceeded response.
     *
     * @param  Request  $request  The incoming request
     * @param  int  $retryAfter  Seconds until rate limit resets
     * @return Response 429 response with retry information
     */
    protected function buildResponse(Request $request, int $retryAfter): Response
    {
        $message = 'Too many requests. Please try again later.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response($message, 429)
            ->header('Retry-After', $retryAfter)
            ->header('X-RateLimit-Limit', config('throttle.requests', 60))
            ->header('X-RateLimit-Remaining', 0);
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param  Response  $response  The outgoing response
     * @param  int  $maxAttempts  Maximum allowed attempts
     * @param  int  $remainingAttempts  Remaining attempts
     * @return Response Response with rate limit headers
     */
    protected function addHeaders(
        Response $response,
        int $maxAttempts,
        int $remainingAttempts
    ): Response {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remainingAttempts));

        return $response;
    }
}
