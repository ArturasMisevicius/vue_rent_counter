<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use App\Services\TenantBoundaryService;

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
final readonly class InvoicePolicy
{
    public function __construct(
        private TenantBoundaryService $tenantBoundaryService
    ) {}

    /**
     * Determine whether the user can view any invoices.
     * 
     * All authenticated users can view invoices (filtered by tenant scope).
     * TenantScope global scope ensures users only see their tenant's data.
     * 
     * Requirements: 11.1, 11.4
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view invoices
        // TenantScope handles data filtering automatically
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
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Managers and above can view all invoices in their tenant
        if ($this->tenantBoundaryService->canPerformManagerOperations($user)) {
            return true;
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
        // User must have a tenant_id and have appropriate role
        if ($user->tenant_id === null) {
            return false;
        }

        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Finalized invoices cannot be updated (except by superadmin for status changes)
        if ($invoice->isFinalized() && !$user->hasRole('superadmin')) {
            return false;
        }

        // Managers and above can update invoices in their tenant
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
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
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Only draft invoices can be finalized
        if (!$invoice->isDraft()) {
            return false;
        }

        // Managers and above can finalize invoices
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can process payments for the invoice.
     *
     * Allows admins and managers to record payments within their tenant scope.
     * Tenants have read-only access.
     */
    public function processPayment(User $user, Invoice $invoice): bool
    {
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Managers and above can process payments
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can delete the invoice.
     * Managers can delete draft invoices (Permissive workflow).
     * 
     * Requirements: 11.1, 13.3
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Only draft invoices can be deleted
        if (!$invoice->isDraft()) {
            return false;
        }

        // Managers and above can delete invoices
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can restore the invoice.
     * 
     * Requirements: 11.1, 13.3
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Only admins and above can restore invoices
        return $this->tenantBoundaryService->canPerformAdminOperations($user);
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        // Must be able to access the invoice's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $invoice)) {
            return false;
        }

        // Only superadmin can force delete invoices
        return $user->hasRole('superadmin');
    }
}
