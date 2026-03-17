<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $invoice->organization_id;
        }

        if (! $user->isTenant()) {
            return false;
        }

        return $invoice->tenant_user_id === $user->id;
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
