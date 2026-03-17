<?php

namespace App\Actions\Admin\Tenants;

use App\Enums\UserStatus;
use App\Models\User;

class ToggleTenantStatusAction
{
    public function handle(User $tenant): User
    {
        $tenant->update([
            'status' => $tenant->status === UserStatus::ACTIVE
                ? UserStatus::INACTIVE
                : UserStatus::ACTIVE,
        ]);

        return $tenant->fresh();
    }
}
