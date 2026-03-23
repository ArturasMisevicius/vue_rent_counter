<?php

namespace App\Filament\Support\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class LoginRedirector
{
    public function for(User $user): string
    {
        if ($user->isAdmin() && blank($user->organization_id)) {
            return route('welcome.show');
        }

        if ($user->role === UserRole::TENANT && Route::has('filament.admin.pages.tenant-dashboard')) {
            return route('filament.admin.pages.tenant-dashboard');
        }

        return match ($user->role) {
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT => route('filament.admin.pages.dashboard'),
        };
    }
}
