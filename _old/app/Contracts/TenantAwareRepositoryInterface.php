<?php

namespace App\Contracts;

use App\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for tenant-aware repositories
 */
interface TenantAwareRepositoryInterface
{
    /**
     * Set the tenant context for the repository
     */
    public function setTenantContext(TenantId $tenantId): void;

    /**
     * Get the current tenant context
     */
    public function getTenantContext(): ?TenantId;

    /**
     * Clear the tenant context
     */
    public function clearTenantContext(): void;

    /**
     * Find a model by ID within the current tenant context
     */
    public function findById(int $id): ?Model;

    /**
     * Find a model by ID within a specific tenant context
     */
    public function findByIdForTenant(int $id, TenantId $tenantId): ?Model;

    /**
     * Get all models within the current tenant context
     */
    public function getAll(): Collection;

    /**
     * Get all models within a specific tenant context
     */
    public function getAllForTenant(TenantId $tenantId): Collection;

    /**
     * Get paginated models within the current tenant context
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get paginated models within a specific tenant context
     */
    public function getPaginatedForTenant(TenantId $tenantId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new model within the current tenant context
     */
    public function create(array $data): Model;

    /**
     * Create a new model within a specific tenant context
     */
    public function createForTenant(array $data, TenantId $tenantId): Model;

    /**
     * Update a model within the current tenant context
     */
    public function update(int $id, array $data): ?Model;

    /**
     * Update a model within a specific tenant context
     */
    public function updateForTenant(int $id, array $data, TenantId $tenantId): ?Model;

    /**
     * Delete a model within the current tenant context
     */
    public function delete(int $id): bool;

    /**
     * Delete a model within a specific tenant context
     */
    public function deleteForTenant(int $id, TenantId $tenantId): bool;

    /**
     * Count models within the current tenant context
     */
    public function count(): int;

    /**
     * Count models within a specific tenant context
     */
    public function countForTenant(TenantId $tenantId): int;

    /**
     * Execute a query without tenant scoping
     */
    public function withoutTenantScope(callable $callback);
}