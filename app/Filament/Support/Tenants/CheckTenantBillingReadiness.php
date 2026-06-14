<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenants;

use App\Enums\BillingMethod;
use App\Enums\BillingReadinessStatus;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;

final class CheckTenantBillingReadiness
{
    public function handle(User $tenant, ?Property $property = null): TenantBillingReadinessResult
    {
        if (! $tenant->isTenant() || $tenant->organization_id === null) {
            return $this->result(
                BillingReadinessStatus::BLOCKED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.invalid_tenant')],
                nextSteps: ['open_tenant_profile'],
            );
        }

        $assignment = $tenant->relationLoaded('currentPropertyAssignment')
            ? $tenant->currentPropertyAssignment
            : $tenant->currentPropertyAssignment()
                ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'status', 'is_primary', 'assigned_at', 'unassigned_at'])
                ->with(['property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm'])
                ->first();

        $property ??= $assignment?->property;

        if (! $property instanceof Property) {
            return $this->result(
                BillingReadinessStatus::NOT_CONFIGURED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.no_property_assignment')],
                nextSteps: ['assign_property'],
                checks: [$this->check('property_assignment', 'blocked', __('admin.tenants.billing_readiness.errors.no_property_assignment'))],
            );
        }

        return $this->forProspectiveAssignment(
            organization: Organization::query()->select(['id'])->findOrFail((int) $tenant->organization_id),
            property: $property,
            tenantHasPortalAccess: $tenant->canAccessTenantPortal() || $tenant->latestTenantInvitationRecord()?->isPending() === true,
        );
    }

    public function forProspectiveAssignment(
        Organization $organization,
        ?Property $property,
        bool $tenantHasPortalAccess = true,
    ): TenantBillingReadinessResult {
        if (! $property instanceof Property) {
            return $this->result(
                BillingReadinessStatus::NOT_CONFIGURED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.no_property_assignment')],
                nextSteps: ['assign_property'],
                checks: [$this->check('property_assignment', 'blocked', __('admin.tenants.billing_readiness.errors.no_property_assignment'))],
            );
        }

        if ((int) $property->organization_id !== (int) $organization->id) {
            return $this->result(
                BillingReadinessStatus::BLOCKED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.property_scope_mismatch')],
                nextSteps: ['select_property'],
                checks: [$this->check('property_assignment', 'blocked', __('admin.tenants.billing_readiness.errors.property_scope_mismatch'))],
            );
        }

        $serviceConfigurations = $this->activeServiceConfigurations($organization, $property);

        if ($serviceConfigurations->isEmpty()) {
            return $this->result(
                BillingReadinessStatus::NOT_CONFIGURED,
                blockingErrors: [__('admin.tenants.billing_readiness.errors.no_active_services')],
                nextSteps: ['configure_services'],
                checks: [
                    $this->check('property_assignment', 'ready', $property->displayName()),
                    $this->check('services', 'blocked', __('admin.tenants.billing_readiness.errors.no_active_services')),
                ],
            );
        }

        $blockingErrors = [];
        $warnings = [];
        $nextSteps = [];
        $checks = [
            $this->check('property_assignment', 'ready', $property->displayName()),
            $this->check('services', 'ready', trans_choice('admin.tenants.billing_readiness.messages.active_services_count', $serviceConfigurations->count(), [
                'count' => $serviceConfigurations->count(),
            ])),
        ];

        foreach ($serviceConfigurations as $configuration) {
            if ($configuration->hasConfigurationErrors()) {
                $blockingErrors[] = __('admin.tenants.billing_readiness.errors.service_configuration_error', [
                    'service' => $configuration->service_name,
                ]);
                $nextSteps[] = 'fix_service_configuration';
            }

            if ($this->requiresTariff($configuration)) {
                $blockingErrors[] = __('admin.tenants.billing_readiness.errors.missing_tariff', [
                    'service' => $configuration->service_name,
                ]);
                $nextSteps[] = 'configure_tariff';
            }
        }

        $requiresReadings = $serviceConfigurations->contains(
            fn (ServiceConfiguration $configuration): bool => $configuration->requiresConsumptionData(),
        );

        if ($requiresReadings) {
            $meters = Meter::query()
                ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'status'])
                ->forOrganization((int) $organization->id)
                ->forProperty((int) $property->id)
                ->active()
                ->withExists(['readings as has_opening_reading'])
                ->get();

            if ($meters->isEmpty()) {
                $blockingErrors[] = __('admin.tenants.billing_readiness.errors.meter_required');
                $nextSteps[] = 'add_meter';
                $checks[] = $this->check('meters', 'blocked', __('admin.tenants.billing_readiness.errors.meter_required'));
            } else {
                $checks[] = $this->check('meters', 'ready', trans_choice('admin.tenants.billing_readiness.messages.active_meters_count', $meters->count(), [
                    'count' => $meters->count(),
                ]));

                $missingOpeningReadings = $meters
                    ->filter(fn (Meter $meter): bool => ! (bool) $meter->getAttribute('has_opening_reading'))
                    ->map(fn (Meter $meter): string => (string) ($meter->identifier ?: $meter->name))
                    ->values()
                    ->all();

                if ($missingOpeningReadings !== []) {
                    $warnings[] = __('admin.tenants.billing_readiness.warnings.opening_reading_missing', [
                        'meters' => implode(', ', $missingOpeningReadings),
                    ]);
                    $nextSteps[] = 'add_opening_readings';
                    $checks[] = $this->check('opening_readings', 'warning', __('admin.tenants.billing_readiness.warnings.opening_reading_missing', [
                        'meters' => implode(', ', $missingOpeningReadings),
                    ]));
                }
            }

            if (! $tenantHasPortalAccess) {
                $warnings[] = __('admin.tenants.billing_readiness.warnings.portal_access_recommended');
                $nextSteps[] = 'send_invitation';
                $checks[] = $this->check('portal_access', 'warning', __('admin.tenants.billing_readiness.warnings.portal_access_recommended'));
            }
        } else {
            $checks[] = $this->check('meters', 'ready', __('admin.tenants.billing_readiness.messages.no_meter_readings_required'));
        }

        if ($blockingErrors !== []) {
            return $this->result(BillingReadinessStatus::BLOCKED, $blockingErrors, $warnings, $nextSteps, $checks);
        }

        if ($warnings !== []) {
            return $this->result(BillingReadinessStatus::WARNING, $blockingErrors, $warnings, $nextSteps, $checks);
        }

        return $this->result(
            BillingReadinessStatus::READY,
            nextSteps: ['generate_first_invoice'],
            checks: $checks,
        );
    }

    /**
     * @return Collection<int, ServiceConfiguration>
     */
    private function activeServiceConfigurations(Organization $organization, Property $property): Collection
    {
        return ServiceConfiguration::query()
            ->forOrganization((int) $organization->id)
            ->forPropertyValue($property->id)
            ->activeOn(now())
            ->ordered()
            ->get();
    }

    private function requiresTariff(ServiceConfiguration $configuration): bool
    {
        if (! $configuration->billing_method instanceof BillingMethod) {
            return false;
        }

        if (! $configuration->billing_method->createsAutomaticInvoiceItems() || $configuration->billing_method->isFree()) {
            return false;
        }

        if ($configuration->billing_method->requiresFixedAmount()) {
            return $configuration->fixed_amount === null;
        }

        return $configuration->tariff_id === null;
    }

    /**
     * @param  array<int, string>  $blockingErrors
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $nextSteps
     * @param  array<int, array{label: string, status: string, message: string|null}>  $checks
     */
    private function result(
        BillingReadinessStatus $status,
        array $blockingErrors = [],
        array $warnings = [],
        array $nextSteps = [],
        array $checks = [],
    ): TenantBillingReadinessResult {
        return new TenantBillingReadinessResult(
            status: $status,
            blockingErrors: array_values(array_unique($blockingErrors)),
            warnings: array_values(array_unique($warnings)),
            nextSteps: array_values(array_unique($nextSteps)),
            checks: $checks,
        );
    }

    /**
     * @return array{label: string, status: string, message: string|null}
     */
    private function check(string $key, string $status, ?string $message): array
    {
        return [
            'label' => __("admin.tenants.billing_readiness.checks.{$key}"),
            'status' => $status,
            'message' => $message,
        ];
    }
}
