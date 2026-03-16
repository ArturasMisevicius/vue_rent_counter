<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
 * @see EnsureUserIsAdminOrManager
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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $logged = false;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return $this->buildRateLimitResponse($key);
        }

        if (! $request->user()) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            $this->logAuthorizationFailure($request);

            return response(__('app.auth.authentication_required'), 403, [
                'Location' => url('/login'),
            ]);
        }

        $userRole = $request->user()?->role?->value;
        $allowedRoles = ['admin', 'manager', 'superadmin'];

        if ($userRole === null || ! in_array($userRole, $allowedRoles, true)) {
            $this->logAuthorizationFailure($request);
            $logged = true;
        }

        try {
            $response = $next($request);
        } catch (AuthenticationException $exception) {
            $response = response(__('app.auth.authentication_required'), 403, [
                'Location' => url('/login'),
            ]);
        } catch (HttpException $exception) {
            $response = response($exception->getMessage(), $exception->getStatusCode(), $exception->getHeaders());
        } catch (\Throwable $exception) {
            $response = response(__('app.errors.generic', [], null) ?? 'Forbidden', 403);
        }

        if ($response->isSuccessful() || $response->isRedirection()) {
            RateLimiter::clear($key);

            return $response;
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);
        if (! $logged) {
            $this->logAuthorizationFailure($request);
        }

        if ($response->status() === 403) {
            return response(__('app.errors.forbidden', [], null) ?? __('app.auth.no_permission_admin_panel'), 403);
        }

        return $response;
    }

    /**
     * Log authorization failure for security monitoring.
     */
    private function logAuthorizationFailure(Request $request): void
    {
        $user = $request->user();
        $email = $user?->email ? str_replace(["\n", "\r"], ' ', $user->email) : null;

        Log::warning('Admin panel access denied', [
            'user_id' => $user?->id,
            'user_email' => $email,
            'user_role' => $user?->role?->value,
            'reason' => $user ? 'Insufficient role privileges' : 'No authenticated user',
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Resolve the rate limit key for the request.
     */
    private function resolveRequestSignature(Request $request): string
    {
        $ip = (string) $request->ip();

        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            $ip = '127.0.0.1';
        }

        return 'admin-access:'.$ip;
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
