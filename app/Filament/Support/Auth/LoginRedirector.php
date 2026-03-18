<?php

namespace App\Filament\Support\Auth;

use App\Enums\UserRole;
use App\Models\User;

class LoginRedirector
{
    public function for(User $user): string
    {
        if ($user->isAdmin() && blank($user->organization_id)) {
            return route('welcome.show');
        }

        return match ($user->role) {
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER => route('filament.admin.pages.dashboard'),
            UserRole::TENANT => route('filament.admin.pages.tenant-dashboard'),
        };
    }
}
