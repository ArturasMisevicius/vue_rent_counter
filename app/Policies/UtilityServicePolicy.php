<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UtilityService;

class UtilityServicePolicy
{
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
        return $user->isAdminLike();
    }

    public function update(User $user, UtilityService $utilityService): bool
    {
        return $this->view($user, $utilityService);
    }

    public function delete(User $user, UtilityService $utilityService): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $utilityService->organization_id === $user->organization_id;
    }
}
