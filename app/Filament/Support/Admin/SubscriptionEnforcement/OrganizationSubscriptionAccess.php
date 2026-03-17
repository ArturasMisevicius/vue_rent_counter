<?php

namespace App\Filament\Support\Admin\SubscriptionEnforcement;

use App\Enums\SubscriptionAccessMode;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;

class OrganizationSubscriptionAccess
{
    public function forOrganization(Organization|int|null $organization): SubscriptionAccessState
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        if ($organizationId === null) {
            return new SubscriptionAccessState(SubscriptionAccessMode::ACTIVE);
        }

        $subscription = Subscription::query()
            ->select([
                'id',
                'organization_id',
                'plan',
                'status',
                'starts_at',
                'expires_at',
                'is_trial',
                'property_limit_snapshot',
                'tenant_limit_snapshot',
                'meter_limit_snapshot',
                'invoice_limit_snapshot',
            ])
            ->forOrganization($organizationId)
            ->latestFirst()
            ->first();

        if ($subscription === null) {
            return new SubscriptionAccessState(SubscriptionAccessMode::ACTIVE);
        }

        $mode = $this->modeFor($subscription);
        $graceEndsAt = $subscription->expires_at?->copy()->addDays((int) config('tenanto.subscription.grace_period_days', 7));
        $usage = $this->usageFor($organizationId);
        $limits = [
            'properties' => $subscription->property_limit_snapshot,
            'tenants' => $subscription->tenant_limit_snapshot,
            'meters' => $subscription->meter_limit_snapshot,
            'invoices' => $subscription->invoice_limit_snapshot,
        ];

        $limitHits = collect($limits)
            ->filter(fn (?int $limit, string $resource): bool => $limit !== null && ($usage[$resource] ?? 0) >= $limit)
            ->keys()
            ->values()
            ->all();

        if ($mode === SubscriptionAccessMode::ACTIVE && $limitHits !== []) {
            $mode = SubscriptionAccessMode::LIMIT_BLOCKED;
        }

        return new SubscriptionAccessState(
            mode: $mode,
            subscription: $subscription,
            limitHits: $limitHits,
            graceEndsAt: $graceEndsAt,
            usage: $usage,
            limits: $limits,
        );
    }

    private function modeFor(Subscription $subscription): SubscriptionAccessMode
    {
        return match ($subscription->status) {
            SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING => SubscriptionAccessMode::ACTIVE,
            SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED => $this->isInsideGracePeriod($subscription)
                ? SubscriptionAccessMode::GRACE_READ_ONLY
                : SubscriptionAccessMode::POST_GRACE_READ_ONLY,
            SubscriptionStatus::SUSPENDED => SubscriptionAccessMode::POST_GRACE_READ_ONLY,
        };
    }

    private function isInsideGracePeriod(Subscription $subscription): bool
    {
        if ($subscription->expires_at === null) {
            return false;
        }

        return now()->lessThanOrEqualTo(
            $subscription->expires_at->copy()->addDays((int) config('tenanto.subscription.grace_period_days', 7)),
        );
    }

    /**
     * @return array{properties: int, tenants: int, meters: int, invoices: int}
     */
    private function usageFor(int $organizationId): array
    {
        return [
            'properties' => Property::query()
                ->forOrganization($organizationId)
                ->count(),
            'tenants' => User::query()
                ->forOrganization($organizationId)
                ->tenants()
                ->count(),
            'meters' => Meter::query()
                ->forOrganization($organizationId)
                ->count(),
            'invoices' => Invoice::query()
                ->forOrganization($organizationId)
                ->count(),
        ];
    }
}
