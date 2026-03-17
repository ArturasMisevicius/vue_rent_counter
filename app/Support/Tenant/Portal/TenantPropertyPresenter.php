<?php

namespace App\Support\Tenant\Portal;

use App\Models\User;

class TenantPropertyPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $tenant): array
    {
        $tenant = User::query()
            ->select(['id', 'organization_id'])
            ->with([
                'currentPropertyAssignment:id,property_id,tenant_user_id,assigned_at,unassigned_at',
                'currentPropertyAssignment.property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->orderBy('name'),
                'currentPropertyAssignment.property.meters.latestReading:id,meter_id,reading_value,reading_date,validation_status',
            ])
            ->findOrFail($tenant->id);

        $property = $tenant->currentProperty;

        abort_if($property === null, 404);

        return [
            'property_name' => $property->name,
            'property_address' => $property->address,
            'assigned_since' => optional($tenant->currentPropertyAssignment?->assigned_at)?->format('Y-m-d'),
            'meters' => $property->meters->map(fn ($meter) => [
                'id' => $meter->id,
                'name' => $meter->name,
                'identifier' => $meter->identifier,
                'unit' => $meter->unit,
                'last_reading' => $meter->latestReading
                    ? 'Last reading: '.number_format((float) $meter->latestReading->reading_value, 3).' '.$meter->unit.' on '.$meter->latestReading->reading_date->format('Y-m-d')
                    : 'Last reading: None recorded yet',
                'has_reading' => $meter->latestReading !== null,
            ])->all(),
        ];
    }
}
