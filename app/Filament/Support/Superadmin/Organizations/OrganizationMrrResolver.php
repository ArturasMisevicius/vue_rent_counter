<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;

final class OrganizationMrrResolver
{
    public function monthlyAmountFor(Organization $organization): float
    {
        $subscription = $organization->relationLoaded('currentSubscription')
            ? $organization->currentSubscription
            : $organization->currentSubscription()->withLatestPaymentSummary()->first();

        if (! $subscription instanceof Subscription) {
            return 0.0;
        }

        return $this->monthlyAmountForSubscription($subscription);
    }

    public function monthlyAmountForSubscription(Subscription $subscription): float
    {
        $payment = $subscription->relationLoaded('latestPayment')
            ? $subscription->latestPayment
            : $subscription->latestPayment()->first();

        if (! $payment instanceof SubscriptionPayment) {
            return 0.0;
        }

        $months = max(1, $payment->duration?->months() ?? 1);

        return round(((float) $payment->amount) / $months, 2);
    }

    public function displayFor(Organization $organization): string
    {
        $subscription = $organization->relationLoaded('currentSubscription')
            ? $organization->currentSubscription
            : $organization->currentSubscription()->withLatestPaymentSummary()->first();

        if (! $subscription instanceof Subscription) {
            return '—';
        }

        $payment = $subscription->relationLoaded('latestPayment')
            ? $subscription->latestPayment
            : $subscription->latestPayment()->first();

        if (! $payment instanceof SubscriptionPayment) {
            return '—';
        }

        return sprintf(
            '%s %s',
            $payment->currency,
            number_format($this->monthlyAmountForSubscription($subscription), 2, '.', ''),
        );
    }
}
