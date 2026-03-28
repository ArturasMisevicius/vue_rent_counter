<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        app(AuditLogger::class)->created($user);
    }

    public function updated(User $user): void
    {
        $originalRole = $user->getOriginal('role');
        $currentRole = $user->role;

        $originalRoleValue = $originalRole instanceof UserRole ? $originalRole->value : (string) $originalRole;
        $currentRoleValue = $currentRole instanceof UserRole ? $currentRole->value : (string) $currentRole;

        if ($user->wasChanged('role') && $originalRoleValue === UserRole::MANAGER->value && $currentRoleValue !== UserRole::MANAGER->value) {
            $organization = $user->organization;

            if ($organization !== null) {
                $actor = auth()->user();

                app(ManagerPermissionService::class)->resetToDefaults(
                    $user,
                    $organization,
                    $actor instanceof User ? $actor : $user,
                );
            }
        }

        app(AuditLogger::class)->updated($user);
    }

    public function deleted(User $user): void
    {
        app(AuditLogger::class)->deleted($user);
    }
}
