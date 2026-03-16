<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Rate Limiting Middleware for Billing Operations
 * 
 * Protects expensive billing operations from abuse and DoS attacks.
 * 
 * Security Features:
 * - Per-user rate limiting
 * - Configurable limits
 * - Audit logging of violations
 * - IP-based fallback for unauthenticated requests
 */
class RateLimitBilling
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 10, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Billing rate limit exceeded', [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
            ]);
            
            throw new TooManyRequestsHttpException(
                RateLimiter::availableIn($key),
                'Too many billing requests. Please try again later.'
            );
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::retriesLeft($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return 'billing:user:' . $user->id;
        }

        return 'billing:ip:' . $request->ip();
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        return $response;
    }
}
