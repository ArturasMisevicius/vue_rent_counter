<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

/**
 * InvoicePolicy
 * 
 * Authorization policy for invoice operations.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can create and view invoices
 * - 11.4: Tenant can only view their own invoices
 * - 7.3: Cross-tenant access prevention
 * 
 * @package App\Policies
 */
class InvoicePolicy
{
    /**
     * Check if user has admin-level permissions.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is admin or superadmin
     */
    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    /**
     * Determine whether the user can view any invoices.
     * 
     * All authenticated users can view invoices (filtered by tenant scope).
     * 
     * Requirements: 11.1, 11.4
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view invoices (filtered by tenant scope)
        return true;
    }

    /**
     * Determine whether the user can view the invoice.
     * 
     * Adds tenant_id ownership checks.
     * Ensures tenant can only access their property's invoices.
     * 
     * Requirements: 11.1, 11.4, 7.3
     * 
     * @param User $user The authenticated user
     * @param Invoice $invoice The invoice to view
     * @return bool True if authorized
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Superadmin can view any invoice
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and superadmins can view invoices across tenants
        if ($this->isAdmin($user)) {
            return true;
        }

        // Managers can view invoices within their tenant (Requirement 11.3, 7.3)
        if ($user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        // Tenants can only view invoices assigned to them (Requirement 11.4)
        if ($user->role === UserRole::TENANT) {
            $tenantRecord = $user->tenant;

            if (! $tenantRecord) {
                return false;
            }

            return $invoice->tenant_renter_id === $tenantRecord->id
                && $invoice->tenant_id === $tenantRecord->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create invoices.
     * 
     * Admins and Managers can create invoices.
     * Tenants have read-only access.
     * 
     * Requirements: 11.1, 11.3
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function create(User $user): bool
    {
        // Superadmin can create invoices
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins, superadmins, and managers can create invoices (Requirement 11.3)
        return $this->isAdmin($user) || $user->role === UserRole::MANAGER;
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
        if ($this->isAdmin($user) || $user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can finalize the invoice.
     * 
     * Admins and Managers can finalize invoices within their tenant.
     * Only draft invoices can be finalized.
     * 
     * Requirements: 11.1, 11.3, 7.3
     * 
     * @param User $user The authenticated user
     * @param Invoice $invoice The invoice to finalize
     * @return bool True if authorized
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

        // Admins and managers can finalize invoices (Requirement 11.3)
        if ($this->isAdmin($user) || $user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can process payments for the invoice.
     *
     * Allows admins and managers to record payments within their tenant scope.
     * Tenants have read-only access.
     */
    public function processPayment(User $user, Invoice $invoice): bool
    {
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        if ($this->isAdmin($user) || $user->role === UserRole::MANAGER) {
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

        // Only admins and superadmins can delete invoices within their tenant (Requirement 11.1, 13.3)
        if ($this->isAdmin($user)) {
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

        // Only admins and superadmins can restore invoices within their tenant (Requirement 11.1, 13.3)
        if ($this->isAdmin($user)) {
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
