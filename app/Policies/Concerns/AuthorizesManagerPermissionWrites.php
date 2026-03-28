<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\Organization;
use App\Models\User;

trait AuthorizesManagerPermissionWrites
{
    protected function canWriteManagedResource(
        User $user,
        string $resource,
        string $action,
        ?int $recordOrganizationId = null,
    ): bool {
        if ($user->isSuperadmin()) {
            return true;
        }

        $organization = $user->currentOrganization();

        if (! $organization instanceof Organization) {
            return false;
        }

        if ($recordOrganizationId !== null && $recordOrganizationId !== $organization->id) {
            return false;
        }

        if ($user->isAdmin() || $organization->owner_user_id === $user->id) {
            return true;
        }

        if (! $user->hasOrganizationRole($organization, UserRole::MANAGER)) {
            return false;
        }

        return app(ManagerPermissionService::class)->can($user, $organization, $resource, $action);
    }
}
