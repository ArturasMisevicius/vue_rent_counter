<?php

namespace App\Filament\Actions\Admin\Properties;

use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTenantToPropertyAction
{
    public function handle(Property $property, User $tenant, ?float $unitAreaSqm = null): PropertyAssignment
    {
        if (! $tenant->isTenant() || $tenant->organization_id !== $property->organization_id) {
            throw ValidationException::withMessages([
                'tenant' => __('admin.properties.messages.invalid_tenant'),
            ]);
        }

        return DB::transaction(function () use ($property, $tenant, $unitAreaSqm): PropertyAssignment {
            $timestamp = now();
            $currentAssignment = $property->currentAssignment()->first();

            if ($currentAssignment?->tenant_user_id === $tenant->id) {
                $currentAssignment->update([
                    'unit_area_sqm' => $unitAreaSqm,
                ]);

                return $currentAssignment->fresh();
            }

            PropertyAssignment::query()
                ->where('organization_id', $property->organization_id)
                ->where('tenant_user_id', $tenant->id)
                ->whereNull('unassigned_at')
                ->update([
                    'unassigned_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

            if ($currentAssignment !== null) {
                $currentAssignment->update([
                    'unassigned_at' => $timestamp,
                ]);
            }

            return PropertyAssignment::query()->create([
                'organization_id' => $property->organization_id,
                'property_id' => $property->id,
                'tenant_user_id' => $tenant->id,
                'unit_area_sqm' => $unitAreaSqm,
                'assigned_at' => $timestamp,
                'unassigned_at' => null,
            ]);
        });
    }
}
