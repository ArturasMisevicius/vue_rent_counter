<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class ProviderPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Provider $provider): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $provider->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'providers', 'create');
    }

    public function update(User $user, Provider $provider): bool
    {
        return $this->canWriteManagedResource($user, 'providers', 'edit', $provider->organization_id);
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $this->canWriteManagedResource($user, 'providers', 'delete', $provider->organization_id);
    }
}
