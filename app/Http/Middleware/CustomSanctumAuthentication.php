<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Custom Sanctum Authentication Middleware
 * 
 * Handles API token authentication using our custom PersonalAccessToken model
 * while maintaining compatibility with Laravel Sanctum's auth:sanctum middleware.
 */
class CustomSanctumAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // Try to authenticate using bearer token
        if ($token = $this->getTokenFromRequest($request)) {
            // Rate limit token validation attempts per IP
            $rateLimitKey = 'token_validation:' . $request->ip();
            if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
                Log::warning('Token validation rate limit exceeded', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return response()->json(['message' => 'Too many attempts'], 429);
            }

            if ($accessToken = PersonalAccessToken::findToken($token)) {
                if (!$accessToken->isExpired()) {
                    $user = $accessToken->tokenable;
                    
                    // ENHANCED USER VALIDATION
                    if ($user instanceof User && 
                        $user->is_active && 
                        !$user->suspended_at &&
                        $user->email_verified_at !== null) { // SECURITY: Require email verification
                        
                        // Clear rate limit on successful authentication
                        RateLimiter::clear($rateLimitKey);
                        
                        // Set the authenticated user
                        Auth::setUser($user);
                        
                        // Set the current access token on the user
                        $user->currentAccessToken = $accessToken;
                        
                        // Update last used timestamp
                        $accessToken->markAsUsed();
                        
                        return $next($request);
                    }
                }
            }
            
            // Increment rate limit on failed validation
            RateLimiter::hit($rateLimitKey, 300); // 5 minute decay
            
            // Log security event for monitoring
            Log::warning('Invalid token validation attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_prefix' => substr($token, 0, 8) . '...',
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Fall back to session authentication for web routes
        if (Auth::check()) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    /**
     * Get the bearer token from the request.
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');
        
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return $request->input('api_token');
    }
}