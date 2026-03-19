<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\User;

class TenantPropertyPresenter
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $tenant): array
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null || $workspace->propertyId === null) {
            return [
                'has_assignment' => false,
                'property_name' => null,
                'property_address' => null,
                'assigned_since' => null,
                'meters' => [],
            ];
        }

        $organizationId = $workspace->organizationId;

        $tenant = User::query()
            ->select(['id', 'organization_id', 'role'])
            ->with([
                'currentPropertyAssignment' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                    ->forOrganization($organizationId)
                    ->forProperty($workspace->propertyId)
                    ->current(),
                'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->forOrganization($organizationId)
                    ->orderBy('name'),
                'currentPropertyAssignment.property.meters.latestReading' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
                    ->forOrganization($organizationId),
            ])
            ->findOrFail($workspace->userId);

        $property = $tenant->currentProperty;

        if ($property === null) {
            return [
                'has_assignment' => false,
                'property_name' => null,
                'property_address' => null,
                'assigned_since' => null,
                'meters' => [],
            ];
        }

        return [
            'has_assignment' => true,
            'property_name' => $property->name,
            'property_address' => $property->address,
            'assigned_since' => optional($tenant->currentPropertyAssignment?->assigned_at)?->format('Y-m-d'),
            'meters' => $property->meters->map(fn ($meter) => [
                'id' => $meter->id,
                'name' => $meter->name,
                'identifier' => $meter->identifier,
                'unit' => $meter->unit,
                'last_reading' => $meter->latestReading
                    ? __('tenant.pages.property.last_reading', [
                        'value' => number_format((float) $meter->latestReading->reading_value, 3),
                        'unit' => $meter->unit,
                        'date' => $meter->latestReading->reading_date->format('Y-m-d'),
                    ])
                    : __('tenant.pages.property.last_reading_none'),
                'has_reading' => $meter->latestReading !== null,
            ])->all(),
        ];
    }
}
