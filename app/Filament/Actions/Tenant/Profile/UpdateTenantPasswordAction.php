<?php

namespace App\Filament\Actions\Tenant\Profile;

use App\Models\User;

class UpdateTenantPasswordAction
{
    public function handle(User $tenant, string $password): void
    {
        $tenant->forceFill([
            'password' => $password,
        ])->save();
    }
}
