<?php

declare(strict_types=1);

namespace App\Observers;

use App\Contracts\SubscriptionCheckerInterface;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * Observer for Subscription model.
 *
 * Automatically invalidates subscription cache when subscriptions are
 * created, updated, or deleted to ensure data consistency.
 */
class SubscriptionObserver
{
    /**
     * Create a new observer instance.
     */
    public function __construct(
        private readonly SubscriptionCheckerInterface $subscriptionChecker
    ) {}

    /**
     * Handle the Subscription "created" event.
     */
    public function created(Subscription $subscription): void
    {
        $this->invalidateUserCache($subscription, 'created');
    }

    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
        $this->invalidateUserCache($subscription, 'updated');
    }

    /**
     * Handle the Subscription "deleted" event.
     */
    public function deleted(Subscription $subscription): void
    {
        $this->invalidateUserCache($subscription, 'deleted');
    }

    /**
     * Invalidate cache for the subscription's user.
     */
    private function invalidateUserCache(Subscription $subscription, string $action): void
    {
        if ($subscription->user) {
            $this->subscriptionChecker->invalidateCache($subscription->user);

            Log::info('Subscription cache invalidated via observer', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'action' => $action,
            ]);
        }
    }
}
