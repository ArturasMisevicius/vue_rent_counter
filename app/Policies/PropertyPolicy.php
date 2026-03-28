<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class PropertyPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

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
        return $this->canWriteManagedResource($user, 'properties', 'create');
    }

    public function update(User $user, Property $property): bool
    {
        return $this->canWriteManagedResource($user, 'properties', 'edit', $property->organization_id);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->canWriteManagedResource($user, 'properties', 'delete', $property->organization_id);
    }
}
