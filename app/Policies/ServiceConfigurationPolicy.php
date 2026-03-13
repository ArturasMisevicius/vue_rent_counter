<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ServiceConfiguration;
use App\Models\User;

class ServiceConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER], true);
    }

    public function view(User $user, ServiceConfiguration $serviceConfiguration): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        if (!in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER], true)) {
            return false;
        }

        return $user->tenant_id !== null && $serviceConfiguration->tenant_id === $user->tenant_id;
    }

    public function update(User $user, ServiceConfiguration $serviceConfiguration): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        if (!in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER], true)) {
            return false;
        }

        return $user->tenant_id !== null && $serviceConfiguration->tenant_id === $user->tenant_id;
    }
}

