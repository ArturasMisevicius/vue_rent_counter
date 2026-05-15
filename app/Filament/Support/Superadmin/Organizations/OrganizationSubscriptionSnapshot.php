<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;

final readonly class OrganizationSubscriptionSnapshot
{
    /**
     * @param  list<string>  $renewalHistory
     */
    public function __construct(
        public string $currentPlanLabel,
        public string $statusLabel,
        public string $billingCycleLabel,
        public string $nextBillingDateLabel,
        public string $paymentMethodLabel,
        public ?string $trialEndsAtLabel,
        public array $renewalHistory,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        $organization->loadMissing([
            'currentSubscription:id,organization_id,plan,status,starts_at,expires_at,is_trial,property_limit_snapshot,tenant_limit_snapshot,meter_limit_snapshot,invoice_limit_snapshot',
        ]);

        $subscription = $organization->currentSubscription;

        if (! $subscription instanceof Subscription) {
            return new self(
                currentPlanLabel: __('superadmin.organizations.overview.placeholders.no_plan'),
                statusLabel: __('superadmin.organizations.overview.placeholders.no_subscription'),
                billingCycleLabel: __('superadmin.organizations.overview.placeholders.not_available'),
                nextBillingDateLabel: __('superadmin.organizations.overview.placeholders.not_available'),
                paymentMethodLabel: __('superadmin.organizations.overview.payment_method_not_available'),
                trialEndsAtLabel: null,
                renewalHistory: [],
            );
        }

        $subscription->loadMissing([
            'latestPayment:id,organization_id,subscription_id,duration,amount,currency,paid_at',
            'renewals' => fn ($renewalQuery) => $renewalQuery
                ->select([
                    'id',
                    'subscription_id',
                    'user_id',
                    'method',
                    'period',
                    'old_expires_at',
                    'new_expires_at',
                    'duration_days',
                    'notes',
                    'created_at',
                    'updated_at',
                ])
                ->latestFirst(),
        ]);

        return new self(
            currentPlanLabel: $subscription->plan?->label() ?? __('superadmin.organizations.overview.placeholders.no_plan'),
            statusLabel: $subscription->status?->label() ?? __('superadmin.organizations.overview.placeholders.no_subscription'),
            billingCycleLabel: $subscription->latestPayment?->duration?->label()
                ?? __('superadmin.organizations.overview.placeholders.not_available'),
            nextBillingDateLabel: $subscription->expires_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat())
                ?? __('superadmin.organizations.overview.placeholders.not_available'),
            paymentMethodLabel: $subscription->latestPayment !== null
                ? __('superadmin.organizations.overview.payment_method_available')
                : __('superadmin.organizations.overview.payment_method_not_available'),
            trialEndsAtLabel: $subscription->is_trial
                ? $subscription->expires_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat())
                : null,
            renewalHistory: $subscription->renewals
                ->take(5)
                ->map(fn (SubscriptionRenewal $renewal): string => self::renewalLabel($renewal))
                ->all(),
        );
    }

    private static function renewalLabel(SubscriptionRenewal $renewal): string
    {
        $method = mb_convert_case($renewal->method, MB_CASE_TITLE);
        $period = mb_convert_case((string) $renewal->period, MB_CASE_TITLE);
        $date = $renewal->new_expires_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat())
            ?? __('superadmin.organizations.overview.placeholders.not_available');

        return "{$method} · {$period} · {$date}";
    }
}
