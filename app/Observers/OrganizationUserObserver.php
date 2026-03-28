<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\OrganizationUser;
use App\Models\User;

class OrganizationUserObserver
{
    public function updated(OrganizationUser $organizationUser): void
    {
        $originalRole = $organizationUser->getOriginal('role');
        $originalRoleValue = $originalRole instanceof UserRole ? $originalRole->value : (string) $originalRole;

        if (! $organizationUser->wasChanged('role')) {
            return;
        }

        if ($originalRoleValue !== UserRole::MANAGER->value) {
            return;
        }

        if ($organizationUser->role === UserRole::MANAGER->value) {
            return;
        }

        $organizationUser->loadMissing([
            'organization:id,name,slug,status,owner_user_id,created_at,updated_at',
            'user:id,organization_id,name,email,role,status,locale,last_login_at,created_at,updated_at',
        ]);

        if (! $organizationUser->organization || ! $organizationUser->user) {
            return;
        }

        $actor = auth()->user();

        app(ManagerPermissionService::class)->resetToDefaults(
            $organizationUser->user,
            $organizationUser->organization,
            $actor instanceof User ? $actor : $organizationUser->user,
        );
    }
}
