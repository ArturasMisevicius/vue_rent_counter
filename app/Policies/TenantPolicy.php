<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

final class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER], true);
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        if (in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER], true)) {
            return $tenant->tenant_id === $user->tenant_id;
        }

        if ($user->role === UserRole::TENANT) {
            return $user->tenant?->id === $tenant->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER], true);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER], true)
            && $tenant->tenant_id === $user->tenant_id;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER], true)
            && $tenant->tenant_id === $user->tenant_id;
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }
}

