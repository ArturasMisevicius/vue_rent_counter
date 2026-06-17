<?php

namespace App\Policies;

use App\Models\InvoicePayment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class InvoicePaymentPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, InvoicePayment $invoicePayment): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdminLike()) {
            return $user->organization_id === $invoicePayment->organization_id;
        }

        if (! $user->isTenant()) {
            return false;
        }

        return $invoicePayment->tenant_id === $user->id
            && $invoicePayment->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'payments', 'create');
    }

    public function update(User $user, InvoicePayment $invoicePayment): bool
    {
        return $this->canWriteManagedResource($user, 'payments', 'edit', $invoicePayment->organization_id);
    }

    public function delete(User $user, InvoicePayment $invoicePayment): bool
    {
        return $this->canWriteManagedResource($user, 'payments', 'delete', $invoicePayment->organization_id);
    }

    public function confirm(User $user, InvoicePayment $invoicePayment): bool
    {
        return $this->update($user, $invoicePayment);
    }

    public function reject(User $user, InvoicePayment $invoicePayment): bool
    {
        return $this->update($user, $invoicePayment);
    }

    public function void(User $user, InvoicePayment $invoicePayment): bool
    {
        return $this->delete($user, $invoicePayment);
    }
}
