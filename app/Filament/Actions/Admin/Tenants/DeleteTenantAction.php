<?php

namespace App\Filament\Actions\Admin\Tenants;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteTenantAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(User $tenant): void
    {
        $this->subscriptionLimitGuard->ensureCanWrite($tenant->organization_id);

        if ($tenant->invoices()->exists()) {
            throw ValidationException::withMessages([
                'tenant' => __('admin.tenants.messages.delete_blocked'),
            ]);
        }

        $tenant->delete();
    }
}
