<?php

namespace App\Actions\Admin\Properties;

use App\Models\Property;
use App\Models\PropertyAssignment;
use Carbon\CarbonInterface;

class UnassignTenantFromPropertyAction
{
    public function handle(Property $property, ?CarbonInterface $unassignedAt = null): ?PropertyAssignment
    {
        $assignment = $property->currentAssignment()->first();

        if ($assignment === null) {
            return null;
        }

        $assignment->forceFill([
            'unassigned_at' => $unassignedAt ?? now(),
        ])->save();

        return $assignment->fresh();
    }
}
