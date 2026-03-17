<?php

namespace App\Filament\Support\Shell;

use App\Filament\Support\Auth\LoginRedirector;
use App\Models\User;

class DashboardUrlResolver
{
    public function __construct(
        protected LoginRedirector $loginRedirector,
    ) {}

    public function for(?User $user): string
    {
        if ($user === null) {
            return route('login');
        }

        if ($user->isAdmin() && blank($user->organization_id)) {
            return route('welcome.show');
        }

        return route('filament.admin.pages.dashboard');
    }
}
