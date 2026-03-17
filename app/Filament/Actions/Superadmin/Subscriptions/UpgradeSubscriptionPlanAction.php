<?php

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;

class UpgradeSubscriptionPlanAction
{
    public function handle(Subscription $subscription, SubscriptionPlan $plan): Subscription
    {
        $subscription->applyPlanSnapshots($plan);
        $subscription->save();

        return $subscription->fresh();
    }
}
