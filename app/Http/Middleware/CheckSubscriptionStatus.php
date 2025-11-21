<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * Validates that admin users have an active subscription.
     * Allows read-only access for expired subscriptions.
     * Redirects to subscription page if needed.
     *
     * Requirements: 3.4, 3.5
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only check subscription for admin role users
        if (!$user || $user->role !== UserRole::ADMIN) {
            return $next($request);
        }

        // Check if subscription exists
        $subscription = $user->subscription;
        
        if (!$subscription) {
            return $this->redirectToSubscriptionPage(
                'No active subscription found. Please contact support.'
            );
        }

        // Check if subscription is active
        if (!$subscription->isActive()) {
            // Allow read-only access for expired subscriptions (GET requests)
            if ($request->isMethod('GET')) {
                // Add a flash message to inform user about expired subscription
                session()->flash('warning', 'Your subscription has expired. You have read-only access. Please renew to continue managing your properties.');
                return $next($request);
            }

            // Block write operations (POST, PUT, PATCH, DELETE)
            return $this->redirectToSubscriptionPage(
                'Your subscription has expired. Please renew to continue managing your properties.'
            );
        }

        return $next($request);
    }

    /**
     * Redirect to subscription page with error message.
     */
    protected function redirectToSubscriptionPage(string $message): Response
    {
        // For now, redirect to dashboard with error message
        // In the future, this should redirect to a dedicated subscription management page
        return redirect()->route('admin.dashboard')->with('error', $message);
    }
}
