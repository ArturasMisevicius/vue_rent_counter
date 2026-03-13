<?php

declare(strict_types=1);

namespace App\Services\SubscriptionStatusHandlers;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

/**
 * Factory for creating subscription status handlers.
 *
 * Implements Factory pattern to resolve the appropriate handler
 * based on subscription status.
 */
final readonly class SubscriptionStatusHandlerFactory
{
    public function __construct(
        private ActiveSubscriptionHandler $activeHandler,
        private ExpiredSubscriptionHandler $expiredHandler,
        private InactiveSubscriptionHandler $inactiveHandler,
        private MissingSubscriptionHandler $missingHandler,
    ) {}

    /**
     * Get the appropriate handler for the given subscription.
     *
     * Performance: Avoids redundant enum conversion by casting directly in match expression.
     * The Subscription model already casts status to SubscriptionStatus enum via attribute casting.
     *
     * @param  Subscription|null  $subscription  The subscription to handle
     * @return SubscriptionStatusHandler The appropriate handler
     */
    public function getHandler(?Subscription $subscription): SubscriptionStatusHandler
    {
        if (! $subscription) {
            return $this->missingHandler;
        }

        // Direct match on status - Laravel's attribute casting ensures it's already an enum
        return match ($subscription->status) {
            SubscriptionStatus::ACTIVE => $this->activeHandler,
            SubscriptionStatus::EXPIRED => $this->expiredHandler,
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::CANCELLED => $this->inactiveHandler,
        };
    }
}
