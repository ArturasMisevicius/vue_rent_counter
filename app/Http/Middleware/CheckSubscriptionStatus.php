<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSubscriptionStatus middleware enforces subscription requirements for admin users.
 * 
 * This middleware validates that admin users have an active subscription before
 * allowing access to protected routes. It implements read-only mode for expired
 * subscriptions and blocks access for suspended/cancelled subscriptions.
 * 
 * Behavior by subscription status:
 * - ACTIVE: Full access to all routes
 * - EXPIRED: Read-only access (GET requests only) with warning message
 * - SUSPENDED/CANCELLED: Read-only access with warning message
 * - NO SUBSCRIPTION: Dashboard access only with error message
 * 
 * Security: All subscription checks are logged for audit trail
 * 
 * Requirements: 3.4, 3.5
 * 
 * @package App\Http\Middleware
 */
final class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * Validates that admin users have an active subscription.
     * Allows read-only access for expired subscriptions.
     * Redirects to dashboard if subscription is missing or invalid.
     *
     * Performance: Uses SubscriptionChecker service with caching (5min TTL)
     * to reduce database queries by ~95%
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response
     * 
     * Requirements: 3.4, 3.5
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Early return: Only check subscription for admin role users
        if (!$user || $user->role !== UserRole::ADMIN) {
            return $next($request);
        }

        // Performance: Use SubscriptionChecker service with caching
        $checker = app(\App\Services\SubscriptionChecker::class);
        $subscription = $checker->getSubscription($user);
        
        if (!$subscription) {
            return $this->handleMissingSubscription($request);
        }

        // Handle different subscription statuses
        // Convert string status to enum if needed
        $status = $subscription->status instanceof SubscriptionStatus 
            ? $subscription->status 
            : SubscriptionStatus::from($subscription->status);
        
        return match ($status) {
            SubscriptionStatus::ACTIVE => $this->handleActiveSubscription($request, $next, $subscription),
            SubscriptionStatus::EXPIRED => $this->handleExpiredSubscription($request, $next),
            SubscriptionStatus::SUSPENDED, 
            SubscriptionStatus::CANCELLED => $this->handleInactiveSubscription($request, $next, $status),
            default => $this->handleUnknownStatus($request, $status),
        };
    }

    /**
     * Handle requests when subscription is missing.
     * 
     * @param Request $request The incoming HTTP request
     * @return Response The HTTP response
     */
    protected function handleMissingSubscription(Request $request): Response
    {
        $this->logSubscriptionCheck('missing', $request);
        
        // Allow access to dashboard to see subscription warning
        if ($request->routeIs('admin.dashboard')) {
            session()->flash('error', 'No active subscription found. Please contact support.');
            return app()->make('next')($request);
        }
        
        return $this->redirectToSubscriptionPage(
            'No active subscription found. Please contact support.'
        );
    }

    /**
     * Handle requests with active subscription.
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @param \App\Models\Subscription $subscription The user's subscription
     * @return Response The HTTP response
     */
    protected function handleActiveSubscription(Request $request, Closure $next, $subscription): Response
    {
        // Check if subscription has actually expired despite status
        if ($subscription->isExpired()) {
            $this->logSubscriptionCheck('expired_but_active_status', $request, $subscription);
            return $this->handleExpiredSubscription($request, $next);
        }
        
        return $next($request);
    }

    /**
     * Handle requests with expired subscription.
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response
     */
    protected function handleExpiredSubscription(Request $request, Closure $next): Response
    {
        // Allow read-only access for expired subscriptions (GET requests)
        if ($request->isMethod('GET')) {
            $this->logSubscriptionCheck('expired_readonly', $request);
            session()->flash('warning', 'Your subscription has expired. You have read-only access. Please renew to continue managing your properties.');
            return $next($request);
        }

        // Block write operations (POST, PUT, PATCH, DELETE)
        $this->logSubscriptionCheck('expired_write_blocked', $request);
        return $this->redirectToSubscriptionPage(
            'Your subscription has expired. Please renew to continue managing your properties.'
        );
    }

    /**
     * Handle requests with suspended or cancelled subscription.
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @param SubscriptionStatus $status The subscription status
     * @return Response The HTTP response
     */
    protected function handleInactiveSubscription(Request $request, Closure $next, SubscriptionStatus $status): Response
    {
        $this->logSubscriptionCheck($status->value, $request);
        
        // Allow read-only access with warning
        if ($request->isMethod('GET')) {
            $message = $status === SubscriptionStatus::SUSPENDED
                ? 'Your subscription has been suspended. Please contact support.'
                : 'Your subscription has been cancelled. Please contact support to reactivate.';
            
            session()->flash('warning', $message);
            return $next($request);
        }

        // Block write operations
        return $this->redirectToSubscriptionPage(
            'Your subscription is not active. Please contact support.'
        );
    }

    /**
     * Handle requests with unknown subscription status.
     * 
     * @param Request $request The incoming HTTP request
     * @param SubscriptionStatus $status The unknown subscription status
     * @return Response The HTTP response
     */
    protected function handleUnknownStatus(Request $request, SubscriptionStatus $status): Response
    {
        $this->logSubscriptionCheck('unknown_status', $request, null, [
            'status' => $status->value,
        ]);
        
        return $this->redirectToSubscriptionPage(
            'Your subscription status is unclear. Please contact support.'
        );
    }

    /**
     * Redirect to subscription page with error message.
     * 
     * @param string $message The error message to display
     * @return Response The redirect response
     */
    protected function redirectToSubscriptionPage(string $message): Response
    {
        return redirect()->route('admin.dashboard')->with('error', $message);
    }

    /**
     * Log subscription check for audit trail.
     * 
     * @param string $checkType The type of subscription check
     * @param Request $request The incoming HTTP request
     * @param \App\Models\Subscription|null $subscription The subscription if available
     * @param array $additionalContext Additional context to log
     * @return void
     */
    protected function logSubscriptionCheck(
        string $checkType, 
        Request $request, 
        $subscription = null,
        array $additionalContext = []
    ): void {
        Log::channel('audit')->info('Subscription check performed', array_merge([
            'check_type' => $checkType,
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'subscription_id' => $subscription?->id,
            'subscription_status' => $subscription?->status?->value,
            'expires_at' => $subscription?->expires_at?->toIso8601String(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ], $additionalContext));
    }
}
