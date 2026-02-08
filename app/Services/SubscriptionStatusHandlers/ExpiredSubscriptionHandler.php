<?php

declare(strict_types=1);

namespace App\Services\SubscriptionStatusHandlers;

use App\Models\Subscription;
use App\ValueObjects\SubscriptionCheckResult;
use Illuminate\Http\Request;

/**
 * Handler for expired subscriptions.
 *
 * Implements read-only mode: allows GET requests with warning,
 * blocks write operations (POST, PUT, PATCH, DELETE).
 */
final readonly class ExpiredSubscriptionHandler implements SubscriptionStatusHandler
{
    private const DASHBOARD_ROUTE = 'admin.dashboard';

    private const EXPIRED_MESSAGE = 'Your subscription has expired. You have read-only access. Please renew to continue managing your properties.';

    public function handle(Request $request, ?Subscription $subscription): SubscriptionCheckResult
    {
        // Allow read-only access for GET requests
        if ($request->isMethod('GET')) {
            return SubscriptionCheckResult::allowWithWarning(self::EXPIRED_MESSAGE);
        }

        // Block write operations
        return SubscriptionCheckResult::block(
            self::EXPIRED_MESSAGE,
            self::DASHBOARD_ROUTE
        );
    }
}
