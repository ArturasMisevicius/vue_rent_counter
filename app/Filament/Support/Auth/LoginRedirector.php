<?php

namespace App\Filament\Support\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class LoginRedirector
{
    public function for(User $user): string
    {
        if (($user->isAdmin() || $user->isManager()) && blank($user->organization_id) && Route::has('welcome.show')) {
            return route('welcome.show');
        }

        if (Route::has('filament.admin.pages.dashboard')) {
            return route('filament.admin.pages.dashboard');
        }

        return match ($user->role) {
            UserRole::SUPERADMIN => Route::has('filament.admin.pages.platform-dashboard')
                ? route('filament.admin.pages.platform-dashboard')
                : route('filament.admin.pages.dashboard'),
            UserRole::ADMIN,
            UserRole::MANAGER => Route::has('filament.admin.pages.organization-dashboard')
                ? route('filament.admin.pages.organization-dashboard')
                : route('filament.admin.pages.dashboard'),
            UserRole::TENANT => route('filament.admin.pages.dashboard'),
        };
    }
}
