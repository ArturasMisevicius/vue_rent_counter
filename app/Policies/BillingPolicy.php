<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * BillingPolicy
 * 
 * Authorization policy for billing operations.
 * 
 * Requirements:
 * - 11.2: Manager access to invoice generation
 * - 11.3: Admin access to invoice generation
 * - 7.3: Cross-tenant access prevention
 * 
 * @package App\Policies
 */
class BillingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can generate invoices.
     * 
     * Only Managers and Admins can generate invoices.
     * Superadmins can generate invoices for any tenant.
     * 
     * @param User $user The authenticated user
     * @param Tenant $tenant The tenant to generate invoice for
     * @return bool True if authorized
     */
    public function generateInvoice(User $user, Tenant $tenant): bool
    {
        // Superadmins can generate invoices for any tenant
        if ($user->isSuperadmin()) {
            return true;
        }

        // Only Managers and Admins can generate invoices
        if (!$user->isManager() && !$user->isAdmin()) {
            return false;
        }

        // Verify tenant belongs to user's tenant context
        if ($user->tenant_id !== $tenant->tenant_id) {
            return false;
        }

        // Verify tenant belongs to current TenantContext
        if (TenantContext::has() && TenantContext::id() !== $tenant->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can finalize invoices.
     * 
     * Only Managers and Admins can finalize invoices.
     * 
     * @param User $user The authenticated user
     * @param \App\Models\Invoice $invoice The invoice to finalize
     * @return bool True if authorized
     */
    public function finalizeInvoice(User $user, $invoice): bool
    {
        // Superadmins can finalize any invoice
        if ($user->isSuperadmin()) {
            return true;
        }

        // Only Managers and Admins can finalize invoices
        if (!$user->isManager() && !$user->isAdmin()) {
            return false;
        }

        // Verify invoice belongs to user's tenant
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        // Verify invoice belongs to current TenantContext
        if (TenantContext::has() && TenantContext::id() !== $invoice->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can view billing reports.
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function viewReports(User $user): bool
    {
        // Superadmins, Admins, and Managers can view reports
        return $user->isSuperadmin() || $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine if the user can recalculate invoices.
     * 
     * Only Admins and Superadmins can recalculate invoices.
     * 
     * @param User $user The authenticated user
     * @param \App\Models\Invoice $invoice The invoice to recalculate
     * @return bool True if authorized
     */
    public function recalculateInvoice(User $user, $invoice): bool
    {
        // Superadmins can recalculate any invoice
        if ($user->isSuperadmin()) {
            return true;
        }

        // Only Admins can recalculate invoices
        if (!$user->isAdmin()) {
            return false;
        }

        // Verify invoice belongs to user's tenant
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        return true;
    }
}
