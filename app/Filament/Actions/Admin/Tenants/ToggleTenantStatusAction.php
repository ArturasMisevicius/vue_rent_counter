<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\TenantStatus;
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

        $isActive = $tenant->status === UserStatus::ACTIVE;

        $tenant->update([
            'status' => $isActive ? UserStatus::INACTIVE : UserStatus::ACTIVE,
            'tenant_status' => $isActive ? TenantStatus::INACTIVE : TenantStatus::ACTIVE,
            'portal_access_enabled' => ! $isActive,
        ]);

        return $tenant->fresh();
    }
}
