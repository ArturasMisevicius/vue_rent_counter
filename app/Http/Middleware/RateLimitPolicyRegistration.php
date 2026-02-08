<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limit Policy Registration Middleware
 * 
 * Prevents abuse of policy registration endpoints
 */
final class RateLimitPolicyRegistration
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'policy-registration:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Log::warning('Policy registration rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'context' => 'security_violation'
            ]);
            
            abort(429, 'Too many policy registration attempts');
        }
        
        RateLimiter::hit($key, 300); // 5 minutes
        
        return $next($request);
    }
}