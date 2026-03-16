<?php

namespace App\Repositories;

use App\Contracts\TenantAwareRepositoryInterface;
use App\Contracts\TenantContextInterface;
use App\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base repository implementation with tenant awareness
 */
abstract class BaseTenantRepository implements TenantAwareRepositoryInterface
{
    protected Model $model;
    protected ?TenantId $tenantContext = null;
    protected TenantContextInterface $globalTenantContext;

    public function __construct(Model $model, TenantContextInterface $tenantContext)
    {
        $this->model = $model;
        $this->globalTenantContext = $tenantContext;
    }

    /**
     * Set the tenant context for the repository
     */
    public function setTenantContext(TenantId $tenantId): void
    {
        $this->tenantContext = $tenantId;
    }

    /**
     * Get the current tenant context
     */
    public function getTenantContext(): ?TenantId
    {
        return $this->tenantContext ?? $this->globalTenantContext->getCurrentTenant();
    }

    /**
     * Clear the tenant context
     */
    public function clearTenantContext(): void
    {
        $this->tenantContext = null;
    }

    /**
     * Find a model by ID within the current tenant context
     */
    public function findById(int $id): ?Model
    {
        return $this->getQuery()->find($id);
    }

    /**
     * Find a model by ID within a specific tenant context
     */
    public function findByIdForTenant(int $id, TenantId $tenantId): ?Model
    {
        return $this->getQueryForTenant($tenantId)->find($id);
    }

    /**
     * Get all models within the current tenant context
     */
    public function getAll(): Collection
    {
        return $this->getQuery()->get();
    }

    /**
     * Get all models within a specific tenant context
     */
    public function getAllForTenant(TenantId $tenantId): Collection
    {
        return $this->getQueryForTenant($tenantId)->get();
    }

    /**
     * Get paginated models within the current tenant context
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getQuery()->paginate($perPage);
    }

    /**
     * Get paginated models within a specific tenant context
     */
    public function getPaginatedForTenant(TenantId $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getQueryForTenant($tenantId)->paginate($perPage);
    }

    /**
     * Create a new model within the current tenant context
     */
    public function create(array $data): Model
    {
        $tenantContext = $this->getTenantContext();
        if ($tenantContext && method_exists($this->model, 'getTenantIdColumn')) {
            $data[$this->model->getTenantIdColumn()] = $tenantContext->getValue();
        }
        
        return $this->model->create($data);
    }

    /**
     * Create a new model within a specific tenant context
     */
    public function createForTenant(array $data, TenantId $tenantId): Model
    {
        if (method_exists($this->model, 'getTenantIdColumn')) {
            $data[$this->model->getTenantIdColumn()] = $tenantId->getValue();
        }
        
        return $this->model->create($data);
    }

    /**
     * Update a model within the current tenant context
     */
    public function update(int $id, array $data): ?Model
    {
        $model = $this->findById($id);
        if ($model) {
            $model->update($data);
            return $model->fresh();
        }
        
        return null;
    }

    /**
     * Update a model within a specific tenant context
     */
    public function updateForTenant(int $id, array $data, TenantId $tenantId): ?Model
    {
        $model = $this->findByIdForTenant($id, $tenantId);
        if ($model) {
            $model->update($data);
            return $model->fresh();
        }
        
        return null;
    }

    /**
     * Delete a model within the current tenant context
     */
    public function delete(int $id): bool
    {
        $model = $this->findById($id);
        return $model ? $model->delete() : false;
    }

    /**
     * Delete a model within a specific tenant context
     */
    public function deleteForTenant(int $id, TenantId $tenantId): bool
    {
        $model = $this->findByIdForTenant($id, $tenantId);
        return $model ? $model->delete() : false;
    }

    /**
     * Count models within the current tenant context
     */
    public function count(): int
    {
        return $this->getQuery()->count();
    }

    /**
     * Count models within a specific tenant context
     */
    public function countForTenant(TenantId $tenantId): int
    {
        return $this->getQueryForTenant($tenantId)->count();
    }

    /**
     * Execute a query without tenant scoping
     */
    public function withoutTenantScope(callable $callback)
    {
        return $callback($this->model->withoutGlobalScopes());
    }

    /**
     * Get a query builder with current tenant context
     */
    protected function getQuery(): Builder
    {
        $query = $this->model->newQuery();
        
        $tenantContext = $this->getTenantContext();
        if ($tenantContext && method_exists($this->model, 'scopeForTenant')) {
            $query = $query->forTenant($tenantContext);
        }
        
        return $query;
    }

    /**
     * Get a query builder for a specific tenant
     */
    protected function getQueryForTenant(TenantId $tenantId): Builder
    {
        $query = $this->model->newQuery();
        
        if (method_exists($this->model, 'scopeForTenant')) {
            $query = $query->forTenant($tenantId);
        }
        
        return $query;
    }

    /**
     * Apply additional query constraints (can be overridden by child classes)
     */
    protected function applyQueryConstraints(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Get the model class name
     */
    protected function getModelClass(): string
    {
        return get_class($this->model);
    }

    /**
     * Create a new instance of the model
     */
    protected function newModelInstance(array $attributes = []): Model
    {
        return $this->model->newInstance($attributes);
    }
}