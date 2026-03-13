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
 * 
 * @package App\Observers
 */
class SubscriptionObserver
{
    /**
     * Create a new observer instance.
     *
     * @param SubscriptionCheckerInterface $subscriptionChecker
     */
    public function __construct(
        private readonly SubscriptionCheckerInterface $subscriptionChecker
    ) {
    }

    /**
     * Handle the Subscription "created" event.
     *
     * @param Subscription $subscription
     * @return void
     */
    public function created(Subscription $subscription): void
    {
        $this->invalidateUserCache($subscription, 'created');
    }

    /**
     * Handle the Subscription "updated" event.
     *
     * @param Subscription $subscription
     * @return void
     */
    public function updated(Subscription $subscription): void
    {
        $this->invalidateUserCache($subscription, 'updated');
    }

    /**
     * Handle the Subscription "deleted" event.
     *
     * @param Subscription $subscription
     * @return void
     */
    public function deleted(Subscription $subscription): void
    {
        $this->invalidateUserCache($subscription, 'deleted');
    }

    /**
     * Invalidate cache for the subscription's user.
     *
     * @param Subscription $subscription
     * @param string $action
     * @return void
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
