<?php

namespace App\Filament\Actions\Admin\Settings;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;

class RenewOrganizationSubscriptionAction
{
    public function handle(
        Organization $organization,
        SubscriptionPlan $plan,
        SubscriptionDuration $duration,
    ): Subscription {
        $subscription = $organization->subscriptions()
            ->latest('expires_at')
            ->latest('id')
            ->first();

        if ($subscription === null) {
            $subscription = new Subscription;
            $subscription->organization()->associate($organization);
        }

        $limits = $plan->limits();

        $subscription->fill([
            'plan' => $plan,
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => now()->startOfDay(),
            'expires_at' => now()->startOfDay()->addMonths($duration->months()),
            'is_trial' => false,
            'property_limit_snapshot' => $limits['properties'],
            'tenant_limit_snapshot' => $limits['tenants'],
            'meter_limit_snapshot' => $limits['meters'],
            'invoice_limit_snapshot' => $limits['invoices'],
        ]);

        $subscription->save();

        return $subscription->refresh();
    }
}
