<?php

namespace App\Actions\Admin\Tenants;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteTenantAction
{
    public function handle(User $tenant): void
    {
        if ($tenant->invoices()->exists()) {
            throw ValidationException::withMessages([
                'tenant' => __('admin.tenants.messages.delete_blocked'),
            ]);
        }

        $tenant->delete();
    }
}
