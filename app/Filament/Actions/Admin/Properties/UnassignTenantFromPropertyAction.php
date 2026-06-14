<?php

namespace App\Filament\Actions\Admin\Properties;

use App\Enums\PropertyAssignmentStatus;
use App\Enums\PropertyOccupancyStatus;
use App\Filament\Actions\Admin\TenantMoveOut\UpdatePropertyOccupancyStatus;
use App\Models\Property;
use App\Models\PropertyAssignment;

class UnassignTenantFromPropertyAction
{
    public function handle(Property $property): ?PropertyAssignment
    {
        $assignment = $property->currentAssignment()->first();

        if ($assignment === null) {
            return null;
        }

        $assignment->update([
            'unassigned_at' => now(),
            'billing_end_date' => now()->toDateString(),
            'status' => PropertyAssignmentStatus::ENDED,
        ]);

        app(UpdatePropertyOccupancyStatus::class)->handle(
            $property->fresh() ?? $property,
            PropertyOccupancyStatus::VACANT,
            preserveManualHold: false,
        );

        return $assignment->fresh();
    }
}
