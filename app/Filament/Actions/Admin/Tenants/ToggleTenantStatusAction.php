<?php

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\UserStatus;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\User;

class ToggleTenantStatusAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(User $tenant): User
    {
        $this->subscriptionLimitGuard->ensureCanWrite($tenant->organization_id);

        $tenant->update([
            'status' => $tenant->status === UserStatus::ACTIVE
                ? UserStatus::INACTIVE
                : UserStatus::ACTIVE,
        ]);

        return $tenant->fresh();
    }
}
