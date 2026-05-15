<?php

declare(strict_types=1);

namespace App\Filament\Support\Features;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

final class OrganizationFeatureCatalog
{
    public const ADVANCED_REPORTING = 'advanced_reporting';

    public const BULK_INVOICING = 'bulk_invoicing';

    public const RESIDENT_APP = 'resident_app';

    public const UTILITY_BILLING = 'utility_billing';

    public const API_ACCESS = 'api_access';

    public const PRIORITY_SUPPORT = 'priority_support';

    /**
     * @var array<string, array{plans: list<string>, default: bool}>
     */
    private const FEATURES = [
        self::ADVANCED_REPORTING => [
            'plans' => [
                SubscriptionPlan::PROFESSIONAL->value,
                SubscriptionPlan::ENTERPRISE->value,
                SubscriptionPlan::CUSTOM->value,
            ],
            'default' => false,
        ],
        self::BULK_INVOICING => [
            'plans' => [
                SubscriptionPlan::PROFESSIONAL->value,
                SubscriptionPlan::ENTERPRISE->value,
                SubscriptionPlan::CUSTOM->value,
            ],
            'default' => false,
        ],
        self::RESIDENT_APP => [
            'plans' => [
                SubscriptionPlan::BASIC->value,
                SubscriptionPlan::PROFESSIONAL->value,
                SubscriptionPlan::ENTERPRISE->value,
                SubscriptionPlan::CUSTOM->value,
            ],
            'default' => false,
        ],
        self::UTILITY_BILLING => [
            'plans' => [
                SubscriptionPlan::BASIC->value,
                SubscriptionPlan::PROFESSIONAL->value,
                SubscriptionPlan::ENTERPRISE->value,
                SubscriptionPlan::CUSTOM->value,
            ],
            'default' => false,
        ],
        self::API_ACCESS => [
            'plans' => [
                SubscriptionPlan::ENTERPRISE->value,
                SubscriptionPlan::CUSTOM->value,
            ],
            'default' => false,
        ],
        self::PRIORITY_SUPPORT => [
            'plans' => [
                SubscriptionPlan::ENTERPRISE->value,
                SubscriptionPlan::CUSTOM->value,
            ],
            'default' => false,
        ],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::FEATURES);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::FEATURES)
            ->mapWithKeys(fn (array $definition, string $feature): array => [$feature => self::label($feature)])
            ->all();
    }

    public static function normalize(string $feature): string
    {
        return Str::of($feature)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();
    }

    public static function label(string $feature): string
    {
        $feature = self::normalize($feature);

        if (! array_key_exists($feature, self::FEATURES)) {
            return Str::headline(str_replace('_', ' ', $feature));
        }

        return (string) __("superadmin.organizations.features.{$feature}");
    }

    public static function defaultEnabled(string $feature, Organization|User|null $scope, bool $fallback = false): bool
    {
        $feature = self::normalize($feature);
        $definition = self::FEATURES[$feature] ?? null;

        if ($definition === null) {
            return $fallback;
        }

        $organization = self::organizationFromScope($scope);

        if (! $organization instanceof Organization) {
            return $definition['default'];
        }

        $subscription = self::activeSubscriptionFor($organization);

        if (! $subscription instanceof Subscription) {
            return $definition['default'];
        }

        return in_array($subscription->plan->value, $definition['plans'], true);
    }

    public static function organizationFromScope(Organization|User|null $scope): ?Organization
    {
        if ($scope instanceof Organization) {
            return $scope;
        }

        if (! $scope instanceof User || $scope->organization_id === null) {
            return null;
        }

        if ($scope->relationLoaded('organization')) {
            return $scope->organization;
        }

        return $scope->organization()
            ->select(['id', 'name', 'slug', 'status', 'owner_user_id', 'system_tenant_id', 'created_at', 'updated_at'])
            ->first();
    }

    private static function activeSubscriptionFor(Organization $organization): ?Subscription
    {
        if ($organization->relationLoaded('currentSubscription')) {
            $subscription = $organization->currentSubscription;

            if (! $subscription instanceof Subscription) {
                return null;
            }

            return in_array($subscription->status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING], true)
                ? $subscription
                : null;
        }

        return $organization->subscriptions()
            ->select(['id', 'organization_id', 'plan', 'status', 'starts_at', 'expires_at'])
            ->activeLike()
            ->latestFirst()
            ->first();
    }
}
