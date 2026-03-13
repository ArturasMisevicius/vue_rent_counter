<?php

declare(strict_types=1);

namespace App\Services\SubscriptionStatusHandlers;

use App\Models\Subscription;
use App\ValueObjects\SubscriptionCheckResult;
use Illuminate\Http\Request;

/**
 * Handler for active subscriptions.
 *
 * Validates that the subscription hasn't actually expired despite having
 * an ACTIVE status, then allows full access.
 */
final readonly class ActiveSubscriptionHandler implements SubscriptionStatusHandler
{
    public function __construct(
        private ExpiredSubscriptionHandler $expiredHandler
    ) {}

    public function handle(Request $request, ?Subscription $subscription): SubscriptionCheckResult
    {
        // Check if subscription has actually expired despite status
        if ($subscription && $subscription->isExpired()) {
            return $this->expiredHandler->handle($request, $subscription);
        }

        return SubscriptionCheckResult::allow();
    }
}
