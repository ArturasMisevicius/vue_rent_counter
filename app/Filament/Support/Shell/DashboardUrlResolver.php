<?php

namespace App\Filament\Support\Shell;

use App\Filament\Support\Auth\LoginRedirector;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class DashboardUrlResolver
{
    public function __construct(
        protected LoginRedirector $loginRedirector,
    ) {}

    public function for(?User $user, bool $preferTenantDashboard = false): string
    {
        if ($user === null) {
            return route('login');
        }

        if ($preferTenantDashboard && $user->isTenant() && Route::has('filament.admin.pages.tenant-dashboard')) {
            return route('filament.admin.pages.tenant-dashboard');
        }

        return $this->loginRedirector->for($user);
    }
}
