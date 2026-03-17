<?php

namespace App\Filament\Actions\Admin\Properties;

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
        ]);

        return $assignment->fresh();
    }
}
