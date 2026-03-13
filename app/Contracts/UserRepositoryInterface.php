<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Repository Interface
 * 
 * Defines user-specific repository operations extending the base repository
 * functionality with user domain logic and specialized queries.
 * 
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a user by email address.
     * 
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find active users.
     * 
     * @return Collection<int, User>
     */
    public function findActiveUsers(): Collection;

    /**
     * Find users by role.
     * 
     * @param UserRole $role
     * @return Collection<int, User>
     */
    public function findByRole(UserRole $role): Collection;

    /**
     * Find users by tenant ID.
     * 
     * @param int $tenantId
     * @return Collection<int, User>
     */
    public function findByTenant(int $tenantId): Collection;

    /**
     * Find tenant users (role = tenant).
     * 
     * @return Collection<int, User>
     */
    public function findTenantUsers(): Collection;

    /**
     * Find admin users (admin, manager, superadmin roles).
     * 
     * @return Collection<int, User>
     */
    public function findAdminUsers(): Collection;

    /**
     * Find users with unverified email.
     * 
     * @return Collection<int, User>
     */
    public function findUnverifiedUsers(): Collection;

    /**
     * Find users by property ID.
     * 
     * @param int $propertyId
     * @return Collection<int, User>
     */
    public function findByProperty(int $propertyId): Collection;

    /**
     * Find child users created by a parent user.
     * 
     * @param int $parentUserId
     * @return Collection<int, User>
     */
    public function findChildUsers(int $parentUserId): Collection;

    /**
     * Search users by name or email.
     * 
     * @param string $search
     * @return Collection<int, User>
     */
    public function searchUsers(string $search): Collection;

    /**
     * Get users with their relationships loaded.
     * 
     * @param array<string> $relations
     * @return Collection<int, User>
     */
    public function getUsersWithRelations(array $relations = []): Collection;

    /**
     * Count users by role.
     * 
     * @param UserRole $role
     * @return int
     */
    public function countByRole(UserRole $role): int;

    /**
     * Count active users.
     * 
     * @return int
     */
    public function countActiveUsers(): int;

    /**
     * Find users created within date range.
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Collection<int, User>
     */
    public function findCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Find users with last login within date range.
     * 
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Collection<int, User>
     */
    public function findLastLoginBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Find suspended users.
     * 
     * @return Collection<int, User>
     */
    public function findSuspendedUsers(): Collection;

    /**
     * Activate a user account.
     * 
     * @param int $userId
     * @return User
     */
    public function activateUser(int $userId): User;

    /**
     * Deactivate a user account.
     * 
     * @param int $userId
     * @param string|null $reason
     * @return User
     */
    public function deactivateUser(int $userId, ?string $reason = null): User;

    /**
     * Update user's last login timestamp.
     * 
     * @param int $userId
     * @return User
     */
    public function updateLastLogin(int $userId): User;
}