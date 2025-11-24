<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
     * 
     * Requirements: 11.1, 13.3
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Superadmin can view any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view invoices across tenants (other permissions enforce scope)
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Managers can view invoices within their tenant
        if ($user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        // Tenants can only view invoices assigned to them
        if ($user->role === UserRole::TENANT) {
            $tenantRecord = $user->tenant;

            if (! $tenantRecord) {
                return false;
            }

            $isOwner = $invoice->tenant_renter_id === $tenantRecord->id
                && $invoice->tenant_id === $user->tenant_id;

            Log::info('invoice_policy_tenant_view', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_tenant_id' => $user->tenant_id,
                'tenant_record_id' => $tenantRecord->id,
                'tenant_record_email' => $tenantRecord->email,
                'tenant_record_tenant_id' => $tenantRecord->tenant_id,
                'invoice_renter_id' => $invoice->tenant_renter_id,
                'invoice_tenant_id' => $invoice->tenant_id,
                'result' => $isOwner,
            ]);

            return $isOwner;
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
        // Finalized invoices cannot be updated (except by superadmin for status changes)
        if ($invoice->isFinalized() && $user->role !== UserRole::SUPERADMIN) {
            return false;
        }

        // Superadmin can update any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can update draft invoices within their tenant
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

        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the invoice.
     * 
     * Requirements: 11.1, 13.3
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

        // Only admins can delete invoices within their tenant (Requirement 11.1, 13.3)
        if ($user->role === UserRole::ADMIN) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the invoice.
     * 
     * Requirements: 11.1, 13.3
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Superadmin can restore any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can restore invoices within their tenant (Requirement 11.1, 13.3)
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
