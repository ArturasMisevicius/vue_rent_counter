<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UtilityService;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class UtilityServicePolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, UtilityService $utilityService): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if (! ($user->isAdmin() || $user->isManager())) {
            return false;
        }

        return $utilityService->organization_id === $user->organization_id
            || ($utilityService->organization_id === null && $utilityService->is_global_template);
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'utility_services', 'create');
    }

    public function update(User $user, UtilityService $utilityService): bool
    {
        return $this->canWriteManagedResource($user, 'utility_services', 'edit', $utilityService->organization_id);
    }

    public function delete(User $user, UtilityService $utilityService): bool
    {
        return $this->canWriteManagedResource($user, 'utility_services', 'delete', $utilityService->organization_id);
    }
}
