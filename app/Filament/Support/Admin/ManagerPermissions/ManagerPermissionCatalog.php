<?php

namespace App\Filament\Support\Admin\ManagerPermissions;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;

class ManagerPermissionCatalog
{
    /**
     * @return list<string>
     */
    public static function resources(): array
    {
        return [
            'buildings',
            'properties',
            'tenants',
            'meters',
            'meter_readings',
            'billing',
            'invoices',
            'tariffs',
            'providers',
            'service_configurations',
            'utility_services',
        ];
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return ['create', 'edit', 'delete'];
    }

    /**
     * @return array{can_create: bool, can_edit: bool, can_delete: bool}
     */
    public static function defaultFlags(): array
    {
        return [
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ];
    }

    /**
     * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    public static function defaultMatrix(): array
    {
        return collect(self::resources())
            ->mapWithKeys(fn (string $resource): array => [
                $resource => self::defaultFlags(),
            ])
            ->all();
    }

    public static function isValidResource(string $resource): bool
    {
        return in_array($resource, self::resources(), true);
    }

    public static function isValidAction(string $action): bool
    {
        return in_array($action, self::actions(), true);
    }

    public static function flagForAction(string $action): string
    {
        return match ($action) {
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            default => throw new \InvalidArgumentException("Unknown manager permission action [{$action}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $matrix
     * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    public static function normalizeMatrix(array $matrix): array
    {
        $normalized = self::defaultMatrix();

        foreach ($matrix as $resource => $flags) {
            if (! self::isValidResource($resource) || ! is_array($flags)) {
                continue;
            }

            $normalized[$resource] = [
                'can_create' => (bool) ($flags['can_create'] ?? false),
                'can_edit' => (bool) ($flags['can_edit'] ?? false),
                'can_delete' => (bool) ($flags['can_delete'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'buildings' => __('admin.buildings.plural'),
            'properties' => __('admin.properties.plural'),
            'tenants' => __('admin.tenants.plural'),
            'meters' => __('admin.meters.plural'),
            'meter_readings' => __('admin.meter_readings.plural'),
            'billing' => __('admin.manager_permissions.resources.billing'),
            'invoices' => __('admin.invoices.plural'),
            'tariffs' => __('admin.tariffs.plural'),
            'providers' => __('admin.providers.plural'),
            'service_configurations' => __('admin.service_configurations.plural'),
            'utility_services' => __('admin.utility_services.plural'),
        ];
    }

    public static function label(string $resource): string
    {
        return self::labels()[$resource] ?? str($resource)->headline()->toString();
    }

    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            'create' => __('admin.manager_permissions.headers.create'),
            'edit' => __('admin.manager_permissions.headers.edit'),
            'delete' => __('admin.manager_permissions.headers.delete'),
        ];
    }

    /**
     * @param  array<string, array{can_create?: bool, can_edit?: bool, can_delete?: bool}>  $matrix
     * @return list<string>
     */
    public static function summaryLines(array $matrix): array
    {
        $normalized = self::normalizeMatrix($matrix);
        $actionLabels = self::actionLabels();
        $lines = [];

        foreach (self::resources() as $resource) {
            $enabledActions = collect(self::actions())
                ->filter(fn (string $action): bool => (bool) ($normalized[$resource][self::flagForAction($action)] ?? false))
                ->map(fn (string $action): string => $actionLabels[$action])
                ->values()
                ->all();

            if ($enabledActions === []) {
                continue;
            }

            $lines[] = sprintf('%s: %s', self::label($resource), implode(', ', $enabledActions));
        }

        if ($lines !== []) {
            return $lines;
        }

        return [__('admin.manager_permissions.summary.read_only')];
    }

    /**
     * @return array<string, array{name: string, matrix: array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>}>
     */
    public static function presets(): array
    {
        $full = collect(self::defaultMatrix())
            ->map(fn (): array => [
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ])
            ->all();

        $billingResources = [
            'billing',
            'invoices',
            'tariffs',
            'providers',
            'service_configurations',
            'utility_services',
        ];

        $propertyResources = [
            'buildings',
            'properties',
            'tenants',
            'meters',
            'meter_readings',
        ];

        return [
            'read_only' => [
                'name' => __('admin.manager_permissions.presets.read_only'),
                'matrix' => self::defaultMatrix(),
            ],
            'full_access' => [
                'name' => __('admin.manager_permissions.presets.full_access'),
                'matrix' => $full,
            ],
            'billing_manager' => [
                'name' => __('admin.manager_permissions.presets.billing_manager'),
                'matrix' => self::resourceSubsetMatrix($billingResources),
            ],
            'property_manager' => [
                'name' => __('admin.manager_permissions.presets.property_manager'),
                'matrix' => self::resourceSubsetMatrix($propertyResources),
            ],
        ];
    }

    /**
     * @return array<string, array{available: bool, reason: string|null}>
     */
    public static function availabilityForOrganization(Organization $organization): array
    {
        $availability = collect(self::resources())
            ->mapWithKeys(fn (string $resource): array => [
                $resource => ['available' => true, 'reason' => null],
            ])
            ->all();

        if ($organization->status === OrganizationStatus::PENDING || self::hasRestrictedTrialPlan($organization)) {
            foreach (['billing', 'invoices', 'tariffs', 'providers', 'service_configurations', 'utility_services'] as $resource) {
                $availability[$resource] = [
                    'available' => false,
                    'reason' => __('admin.manager_permissions.plan_restricted', [
                        'resource' => self::label($resource),
                    ]),
                ];
            }
        }

        return $availability;
    }

    private static function hasRestrictedTrialPlan(Organization $organization): bool
    {
        $subscription = $organization->relationLoaded('currentSubscription')
            ? $organization->currentSubscription
            : $organization->currentSubscription()
                ->select([
                    'id',
                    'organization_id',
                    'plan',
                    'status',
                    'is_trial',
                    'starts_at',
                    'expires_at',
                    'property_limit_snapshot',
                    'tenant_limit_snapshot',
                    'meter_limit_snapshot',
                    'invoice_limit_snapshot',
                    'created_at',
                    'updated_at',
                ])
                ->first();

        if (! $subscription instanceof Subscription) {
            return false;
        }

        $isTrial = $subscription->is_trial || $subscription->status === SubscriptionStatus::TRIALING;

        if (! $isTrial) {
            return false;
        }

        return in_array($subscription->plan, [
            SubscriptionPlan::STARTER,
            SubscriptionPlan::BASIC,
        ], true);
    }

    /**
     * @param  list<string>  $resources
     * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    private static function resourceSubsetMatrix(array $resources): array
    {
        $matrix = self::defaultMatrix();

        foreach ($resources as $resource) {
            $matrix[$resource] = [
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ];
        }

        return $matrix;
    }
}
