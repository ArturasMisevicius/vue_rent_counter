<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class BuildingPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Building $building): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $building->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'buildings', 'create');
    }

    public function update(User $user, Building $building): bool
    {
        return $this->canWriteManagedResource($user, 'buildings', 'edit', $building->organization_id);
    }

    public function delete(User $user, Building $building): bool
    {
        return $this->canWriteManagedResource($user, 'buildings', 'delete', $building->organization_id);
    }
}
