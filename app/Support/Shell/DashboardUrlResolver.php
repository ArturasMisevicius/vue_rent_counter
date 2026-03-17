<?php

namespace App\Support\Shell;

use App\Models\User;
use App\Support\Auth\LoginRedirector;

class DashboardUrlResolver
{
    public function __construct(
        private readonly LoginRedirector $loginRedirector,
    ) {}

    public function for(?User $user): string
    {
        if (! $user instanceof User) {
            return route('login');
        }

        return $this->loginRedirector->for($user);
    }
}
