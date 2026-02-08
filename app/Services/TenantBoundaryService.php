<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Centralized service for tenant boundary validation across all policies.
 * Ensures consistent tenant access control and prevents cross-tenant data leakage.
 */
final readonly class TenantBoundaryService
{
    /**
     * Check if a user can access resources for a specific tenant.
     */
    public function canAccessTenant(User $user, int $tenantId): bool
    {
        // Superadmin can access any tenant
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // User must belong to the tenant
        return $user->tenant_id === $tenantId;
    }

    /**
     * Check if a user can access a specific model that belongs to a tenant.
     */
    public function canAccessModel(User $user, Model $model): bool
    {
        // If model doesn't use tenant scoping, allow access
        if (!$this->usesTenantScoping($model)) {
            return true;
        }

        // Get tenant ID from model
        $tenantId = $this->getTenantIdFromModel($model);
        
        if ($tenantId === null) {
            return false;
        }

        return $this->canAccessTenant($user, $tenantId);
    }

    /**
     * Check if a user can create resources for the current tenant context.
     */
    public function canCreateForCurrentTenant(User $user): bool
    {
        $currentTenantId = $this->getCurrentTenantId();
        
        if ($currentTenantId === null) {
            return false;
        }

        return $this->canAccessTenant($user, $currentTenantId);
    }

    /**
     * Get the current tenant ID from the tenant context.
     */
    public function getCurrentTenantId(): ?int
    {
        // Get from TenantContext service if available
        if (class_exists(\App\Services\TenantContext::class)) {
            $tenantContext = app(\App\Services\TenantContext::class);
            return $tenantContext->getCurrentTenantId();
        }

        // Fallback to authenticated user's tenant
        $user = auth()->user();
        return $user?->tenant_id;
    }

    /**
     * Check if a model uses tenant scoping (has BelongsToTenant trait).
     */
    private function usesTenantScoping(Model $model): bool
    {
        return in_array(BelongsToTenant::class, class_uses_recursive($model));
    }

    /**
     * Extract tenant ID from a model.
     */
    private function getTenantIdFromModel(Model $model): ?int
    {
        // Try common tenant ID field names
        $tenantFields = ['tenant_id', 'organization_id', 'team_id'];
        
        foreach ($tenantFields as $field) {
            if (isset($model->{$field})) {
                return (int) $model->{$field};
            }
        }

        return null;
    }

    /**
     * Validate that a user has the required role for tenant operations.
     */
    public function hasRequiredRole(User $user, array $allowedRoles): bool
    {
        return $user->hasRole($allowedRoles);
    }

    /**
     * Check if user can perform admin-level operations (admin or superadmin).
     */
    public function canPerformAdminOperations(User $user): bool
    {
        return $this->hasRequiredRole($user, ['superadmin', 'admin']);
    }

    /**
     * Check if user can perform manager-level operations (manager, admin, or superadmin).
     */
    public function canPerformManagerOperations(User $user): bool
    {
        return $this->hasRequiredRole($user, ['superadmin', 'admin', 'manager']);
    }

    /**
     * Get all tenant IDs that a user can access.
     */
    public function getAccessibleTenantIds(User $user): array
    {
        // Superadmin can access all tenants
        if ($user->hasRole('superadmin')) {
            // Return all tenant IDs from the database
            return \App\Models\User::distinct()
                ->whereNotNull('tenant_id')
                ->pluck('tenant_id')
                ->toArray();
        }

        // Regular users can only access their own tenant
        return $user->tenant_id ? [$user->tenant_id] : [];
    }
}