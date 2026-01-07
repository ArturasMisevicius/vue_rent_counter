<?php

namespace App\Repositories;

use App\Contracts\TenantContextInterface;
use App\Models\User;
use App\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tenant-aware user repository
 */
class TenantAwareUserRepository extends BaseTenantRepository
{
    public function __construct(TenantContextInterface $tenantContext)
    {
        parent::__construct(new User(), $tenantContext);
    }

    /**
     * Find users by email within current tenant context
     */
    public function findByEmail(string $email): ?User
    {
        return $this->getQuery()->where('email', $email)->first();
    }

    /**
     * Find users by email within specific tenant context
     */
    public function findByEmailForTenant(string $email, TenantId $tenantId): ?User
    {
        return $this->getQueryForTenant($tenantId)->where('email', $email)->first();
    }

    /**
     * Get active users within current tenant context
     */
    public function getActiveUsers(): Collection
    {
        return $this->getQuery()->where('is_active', true)->get();
    }

    /**
     * Get active users within specific tenant context
     */
    public function getActiveUsersForTenant(TenantId $tenantId): Collection
    {
        return $this->getQueryForTenant($tenantId)->where('is_active', true)->get();
    }

    /**
     * Get users by role within current tenant context
     */
    public function getUsersByRole(string $role): Collection
    {
        return $this->getQuery()->where('role', $role)->get();
    }

    /**
     * Get users by role within specific tenant context
     */
    public function getUsersByRoleForTenant(string $role, TenantId $tenantId): Collection
    {
        return $this->getQueryForTenant($tenantId)->where('role', $role)->get();
    }

    /**
     * Search users by name or email within current tenant context
     */
    public function searchUsers(string $search): Collection
    {
        return $this->getQuery()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * Search users by name or email within specific tenant context
     */
    public function searchUsersForTenant(string $search, TenantId $tenantId): Collection
    {
        return $this->getQueryForTenant($tenantId)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * Get user statistics for current tenant
     */
    public function getUserStats(): array
    {
        $query = $this->getQuery();
        
        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
            'by_role' => $query->selectRaw('role, count(*) as count')
                              ->groupBy('role')
                              ->pluck('count', 'role')
                              ->toArray(),
        ];
    }

    /**
     * Get user statistics for specific tenant
     */
    public function getUserStatsForTenant(TenantId $tenantId): array
    {
        $query = $this->getQueryForTenant($tenantId);
        
        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
            'by_role' => $query->selectRaw('role, count(*) as count')
                              ->groupBy('role')
                              ->pluck('count', 'role')
                              ->toArray(),
        ];
    }
}