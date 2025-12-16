<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * User Repository Implementation
 * 
 * Provides user-specific data access operations with tenant awareness,
 * role-based filtering, and user management functionality.
 * 
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Create a new user repository instance.
     * 
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?User
    {
        try {
            return $this->query->where('email', $email)->first();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByEmail', 'email' => $email]);
            return null;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findActiveUsers(): Collection
    {
        try {
            return $this->query->active()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findActiveUsers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByRole(UserRole $role): Collection
    {
        try {
            return $this->query->ofRole($role)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByRole', 'role' => $role->value]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByTenant(int $tenantId): Collection
    {
        try {
            return $this->query->ofTenant($tenantId)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByTenant', 'tenantId' => $tenantId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findTenantUsers(): Collection
    {
        try {
            return $this->query->tenants()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findTenantUsers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAdminUsers(): Collection
    {
        try {
            return $this->query->admins()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findAdminUsers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findUnverifiedUsers(): Collection
    {
        try {
            return $this->query->unverified()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findUnverifiedUsers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByProperty(int $propertyId): Collection
    {
        try {
            return $this->query->where('property_id', $propertyId)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByProperty', 'propertyId' => $propertyId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findChildUsers(int $parentUserId): Collection
    {
        try {
            return $this->query->where('parent_user_id', $parentUserId)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findChildUsers', 'parentUserId' => $parentUserId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function searchUsers(string $search): Collection
    {
        try {
            return $this->query
                ->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'searchUsers', 'search' => $search]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUsersWithRelations(array $relations = []): Collection
    {
        try {
            $defaultRelations = ['property', 'parentUser', 'subscription'];
            $loadRelations = empty($relations) ? $defaultRelations : $relations;
            
            return $this->query->with($loadRelations)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getUsersWithRelations', 'relations' => $relations]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countByRole(UserRole $role): int
    {
        try {
            return $this->query->ofRole($role)->count();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'countByRole', 'role' => $role->value]);
            return 0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countActiveUsers(): int
    {
        try {
            return $this->query->active()->count();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'countActiveUsers']);
            return 0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        try {
            return $this->query
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findCreatedBetween',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findLastLoginBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        try {
            return $this->query
                ->whereBetween('last_login_at', [$startDate, $endDate])
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findLastLoginBetween',
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d')
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findSuspendedUsers(): Collection
    {
        try {
            return $this->query
                ->whereNotNull('suspended_at')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findSuspendedUsers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function activateUser(int $userId): User
    {
        try {
            return $this->transaction(function () use ($userId) {
                $user = $this->findOrFail($userId);
                $user->update([
                    'is_active' => true,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);
                
                $this->logOperation('activateUser', ['userId' => $userId]);
                return $user;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'activateUser', 'userId' => $userId]);
            throw new \App\Exceptions\RepositoryException("Failed to activate user with ID: {$userId}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateUser(int $userId, ?string $reason = null): User
    {
        try {
            return $this->transaction(function () use ($userId, $reason) {
                $user = $this->findOrFail($userId);
                $user->update([
                    'is_active' => false,
                    'suspended_at' => now(),
                    'suspension_reason' => $reason,
                ]);
                
                $this->logOperation('deactivateUser', ['userId' => $userId, 'reason' => $reason]);
                return $user;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'deactivateUser', 'userId' => $userId]);
            throw new \App\Exceptions\RepositoryException("Failed to deactivate user with ID: {$userId}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateLastLogin(int $userId): User
    {
        try {
            return $this->transaction(function () use ($userId) {
                $user = $this->findOrFail($userId);
                $user->update(['last_login_at' => now()]);
                
                $this->logOperation('updateLastLogin', ['userId' => $userId]);
                return $user;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'updateLastLogin', 'userId' => $userId]);
            throw new \App\Exceptions\RepositoryException("Failed to update last login for user ID: {$userId}", 0, $e);
        }
    }

    /**
     * Find users with specific organization role.
     * 
     * @param int $organizationId
     * @param string $role
     * @return Collection<int, User>
     */
    public function findByOrganizationRole(int $organizationId, string $role): Collection
    {
        try {
            return $this->query
                ->whereHas('organizations', function ($query) use ($organizationId, $role) {
                    $query->where('organization_id', $organizationId)
                          ->wherePivot('role', $role)
                          ->wherePivot('is_active', true);
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findByOrganizationRole',
                'organizationId' => $organizationId,
                'role' => $role
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * Find users assigned to specific tasks.
     * 
     * @param int $taskId
     * @return Collection<int, User>
     */
    public function findByTask(int $taskId): Collection
    {
        try {
            return $this->query
                ->whereHas('taskAssignments', function ($query) use ($taskId) {
                    $query->where('task_id', $taskId);
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByTask', 'taskId' => $taskId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * Get user statistics summary.
     * 
     * @return array<string, mixed>
     */
    public function getUserStats(): array
    {
        try {
            return [
                'total_users' => $this->count(),
                'active_users' => $this->countActiveUsers(),
                'admin_users' => $this->countByRole(UserRole::ADMIN),
                'manager_users' => $this->countByRole(UserRole::MANAGER),
                'tenant_users' => $this->countByRole(UserRole::TENANT),
                'superadmin_users' => $this->countByRole(UserRole::SUPERADMIN),
                'unverified_users' => $this->query->unverified()->count(),
                'suspended_users' => $this->query->whereNotNull('suspended_at')->count(),
            ];
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getUserStats']);
            return [];
        } finally {
            $this->resetQuery();
        }
    }
}