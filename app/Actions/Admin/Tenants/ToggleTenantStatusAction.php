<?php

namespace App\Actions\Admin\Tenants;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Admin\SubscriptionLimitGuard;

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
