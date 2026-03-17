<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $property->organization_id;
        }

        if (! $user->isTenant()) {
            return false;
        }

        $assignedPropertyId = $user->relationLoaded('currentPropertyAssignment')
            ? $user->currentPropertyAssignment?->property_id
            : $user->currentPropertyAssignment()->value('property_id');

        return $assignedPropertyId === $property->id;
    }
}
