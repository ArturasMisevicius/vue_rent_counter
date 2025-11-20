<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view invoices (filtered by tenant scope)
        return true;
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Admins and managers can view all invoices (within their tenant)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Tenants can only view their own invoices
        if ($user->role === UserRole::TENANT) {
            // Check if the user's email matches the tenant's email
            $tenant = $user->tenant;
            return $tenant && $invoice->tenant_renter_id === $tenant->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        // Admins and managers can create invoices
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be updated
        if (!$invoice->isDraft()) {
            return false;
        }

        // Admins and managers can update draft invoices
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can finalize the invoice.
     */
    public function finalize(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be finalized
        if (!$invoice->isDraft()) {
            return false;
        }

        // Admins and managers can finalize invoices
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be deleted
        if (!$invoice->isDraft()) {
            return false;
        }

        // Only admins can delete invoices
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Only admins can restore invoices
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Only admins can force delete invoices
        return $user->role === UserRole::ADMIN;
    }
}
