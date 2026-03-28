<?php

namespace App\Policies;

use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class ServiceConfigurationPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, ServiceConfiguration $serviceConfiguration): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $serviceConfiguration->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'service_configurations', 'create');
    }

    public function update(User $user, ServiceConfiguration $serviceConfiguration): bool
    {
        return $this->canWriteManagedResource($user, 'service_configurations', 'edit', $serviceConfiguration->organization_id);
    }

    public function delete(User $user, ServiceConfiguration $serviceConfiguration): bool
    {
        return $this->canWriteManagedResource($user, 'service_configurations', 'delete', $serviceConfiguration->organization_id);
    }
}
