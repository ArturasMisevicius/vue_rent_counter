<?php

namespace App\Observers;

use App\Models\PropertyAssignment;
use App\Models\User;

class PropertyAssignmentObserver
{
    public function saved(PropertyAssignment $assignment): void
    {
        if ($assignment->unassigned_at !== null || $assignment->organization_id === null) {
            return;
        }

        $tenant = $assignment->relationLoaded('tenant')
            ? $assignment->tenant
            : $assignment->tenant()
                ->select(['id', 'organization_id', 'role'])
                ->first();

        if (! $tenant instanceof User || ! $tenant->isTenant()) {
            return;
        }

        if ($tenant->organization_id !== null) {
            return;
        }

        $tenant->forceFill([
            'organization_id' => $assignment->organization_id,
        ])->saveQuietly();
    }
}
