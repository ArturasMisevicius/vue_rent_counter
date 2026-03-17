<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->isAdmin() || $user->isManager()) {
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

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Property $property): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $property->organization_id;
    }

    public function delete(User $user, Property $property): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $property->organization_id;
    }
}
