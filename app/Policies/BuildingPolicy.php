<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
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
        return $user->isSuperadmin() || $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Building $building): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $building->organization_id;
    }

    public function delete(User $user, Building $building): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $building->organization_id;
    }
}
