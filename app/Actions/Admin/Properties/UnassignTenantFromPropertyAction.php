<?php

namespace App\Actions\Admin\Properties;

use App\Models\Property;
use App\Models\PropertyAssignment;
use Illuminate\Support\Carbon;

class UnassignTenantFromPropertyAction
{
    public function handle(Property $property): ?PropertyAssignment
    {
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

        if ($currentAssignment === null) {
            return null;
        }

        $currentAssignment->forceFill([
            'unassigned_at' => Carbon::now(),
        ])->save();

        return $currentAssignment->refresh();
    }
}
