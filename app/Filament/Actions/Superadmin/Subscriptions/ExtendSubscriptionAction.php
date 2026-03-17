<?php

namespace App\Filament\Actions\Superadmin\Subscriptions;

use App\Enums\SubscriptionDuration;
use App\Models\Subscription;

class ExtendSubscriptionAction
{
    public function handle(Subscription $subscription, SubscriptionDuration $duration): Subscription
    {
        $baseline = $subscription->expires_at?->isFuture() === true
            ? $subscription->expires_at->copy()
            : now()->startOfDay();

        $subscription->update([
            'expires_at' => $baseline->addMonths($duration->months()),
        ]);

        return $subscription->fresh();
    }
}
