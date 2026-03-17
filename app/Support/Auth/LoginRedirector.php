<?php

namespace App\Support\Auth;

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
            UserRole::SUPERADMIN => route('filament.admin.pages.platform-dashboard'),
            UserRole::ADMIN, UserRole::MANAGER => route('filament.admin.pages.organization-dashboard'),
            UserRole::TENANT => route('tenant.home'),
        };
    }
}
