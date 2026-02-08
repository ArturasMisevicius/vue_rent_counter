<?php

declare(strict_types=1);

namespace App\Services\SubscriptionStatusHandlers;

use App\Models\Subscription;
use App\ValueObjects\SubscriptionCheckResult;
use Illuminate\Http\Request;

/**
 * Handler for missing subscriptions.
 *
 * Allows dashboard access to display subscription error,
 * blocks access to all other routes.
 */
final readonly class MissingSubscriptionHandler implements SubscriptionStatusHandler
{
    private const DASHBOARD_ROUTE = 'admin.dashboard';

    private const MISSING_MESSAGE = 'No active subscription found. Please contact support.';

    public function handle(Request $request, ?Subscription $subscription): SubscriptionCheckResult
    {
        // Allow access to dashboard to see subscription error
        if ($request->routeIs(self::DASHBOARD_ROUTE)) {
            return SubscriptionCheckResult::allowWithError(self::MISSING_MESSAGE);
        }

        return SubscriptionCheckResult::block(
            self::MISSING_MESSAGE,
            self::DASHBOARD_ROUTE
        );
    }
}
