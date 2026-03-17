<?php

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

class SuspendSubscriptionAction
{
    public function handle(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        return $subscription->fresh();
    }
}
