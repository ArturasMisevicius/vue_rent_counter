<?php

namespace App\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

class CancelSubscriptionAction
{
    public function __invoke(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        return $subscription->refresh();
    }
}
