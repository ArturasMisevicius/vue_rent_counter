<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Tenant\CreateTenantData;
use App\Data\Tenant\BulkOperationResult;
use App\Models\Organization;
use App\ValueObjects\TenantMetrics;
use Illuminate\Database\Eloquent\Collection;

interface TenantManagementInterface
{
    /**
     * Create a new tenant organization
     */
    public function createTenant(CreateTenantData $data): Organization;

    /**
     * Update tenant settings
     */
    public function updateTenantSettings(Organization $tenant, array $settings): void;

    /**
     * Suspend a tenant with reason
     */
    public function suspendTenant(Organization $tenant, string $reason, int $adminId): void;

    /**
     * Activate a suspended tenant
     */
    public function activateTenant(Organization $tenant, int $adminId): void;

    /**
     * Delete a tenant (soft delete with data retention)
     */
    public function deleteTenant(Organization $tenant, int $adminId): void;

    /**
     * Get comprehensive metrics for a tenant
     */
    public function getTenantMetrics(Organization $tenant): TenantMetrics;

    /**
     * Perform bulk operations on multiple tenants
     */
    public function bulkUpdateTenants(Collection $tenants, array $updates, int $adminId): BulkOperationResult;

    /**
     * Update resource quotas for a tenant
     */
    public function updateResourceQuotas(Organization $tenant, array $quotas, int $adminId): void;

    /**
     * Check if tenant is over any resource limits
     */
    public function checkResourceLimits(Organization $tenant): array;

    /**
     * Get all tenants with filtering and sorting
     */
    public function getAllTenants(array $filters = [], string $sortBy = 'created_at', string $sortDirection = 'desc'): Collection;
}