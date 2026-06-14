<?php

declare(strict_types=1);

namespace App\Policies;

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\AuthorizesSuperadminOnly;

class UserPolicy
{
    use AuthorizesSuperadminOnly;

    public function sendTenantInvitation(User $user, User $tenant): bool
    {
        return $this->canManageTenant($user, $tenant, 'create');
    }

    public function manageTenantPortalAccess(User $user, User $tenant): bool
    {
        return $this->canManageTenant($user, $tenant, 'edit');
    }

    public function accessTenantPortal(User $user, User $tenant): bool
    {
        return $user->is($tenant)
            && $tenant->canAccessTenantPortal();
    }

    private function canManageTenant(User $user, User $tenant, string $action): bool
    {
        if (! $tenant->isTenant() || $tenant->organization_id === null) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->organization_id !== $tenant->organization_id) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isManager()) {
            return false;
        }

        $organization = $tenant->relationLoaded('organization')
            ? $tenant->organization
            : $tenant->organization()
                ->select(['id', 'name', 'slug', 'status', 'owner_user_id', 'created_at', 'updated_at'])
                ->first();

        return $organization instanceof Organization
            && app(ManagerPermissionService::class)->can($user, $organization, 'tenants', $action);
    }
}
