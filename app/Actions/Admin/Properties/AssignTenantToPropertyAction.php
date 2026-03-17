<?php

namespace App\Actions\Admin\Properties;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AssignTenantToPropertyAction
{
    /**
     * @throws ValidationException
     */
    public function handle(Property $property, User $tenant, float|string|null $unitAreaSqm = null): PropertyAssignment
    {
        if (
            $tenant->organization_id !== $property->organization_id
            || $tenant->role !== UserRole::TENANT
        ) {
            throw ValidationException::withMessages([
                'tenant_user_id' => __('admin.properties.messages.invalid_tenant'),
            ]);
        }

        /** @var PropertyAssignment|null $currentAssignment */
        $currentAssignment = $property->currentAssignment()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'assigned_at',
                'unassigned_at',
            ])
            ->first();

        if ($currentAssignment?->tenant_user_id === $tenant->id) {
            $currentAssignment->forceFill([
                'unit_area_sqm' => $unitAreaSqm,
            ])->save();

            return $currentAssignment->refresh();
        }

        if ($currentAssignment !== null) {
            $currentAssignment->forceFill([
                'unassigned_at' => Carbon::now(),
            ])->save();
        }

        return PropertyAssignment::query()->create([
            'organization_id' => $property->organization_id,
            'property_id' => $property->id,
            'tenant_user_id' => $tenant->id,
            'unit_area_sqm' => $unitAreaSqm,
            'assigned_at' => Carbon::now(),
            'unassigned_at' => null,
        ]);
    }
}
