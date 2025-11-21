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
     * Adds tenant_id ownership checks.
     * Ensures tenant can only access their property's invoices.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Superadmin can view any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view all invoices within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        // Tenants can only view invoices for their assigned property
        if ($user->role === UserRole::TENANT) {
            // Check if the invoice belongs to the tenant's assigned property
            return $invoice->property_id === $user->property_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        // Superadmin can create invoices
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can create invoices
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Superadmin can update any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view the edit form for invoices within their tenant
        // (form fields will be disabled for finalized invoices)
        // Actual modification attempts are blocked at the model level
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
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

        // Superadmin can finalize any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can finalize invoices within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
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

        // Superadmin can delete any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can delete invoices within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Superadmin can restore any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can restore invoices within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Only superadmin can force delete invoices
        return $user->role === UserRole::SUPERADMIN;
    }
}
