<?php

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

class CancelSubscriptionAction
{
    public function handle(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
        ]);

        return $subscription->fresh();
    }
}
