<?php

namespace App\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

class SuspendSubscriptionAction
{
    public function __invoke(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        return $subscription->refresh();
    }
}
