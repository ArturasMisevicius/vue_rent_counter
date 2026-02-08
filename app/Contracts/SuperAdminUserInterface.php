<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\User\ActivityReport;
use App\Data\User\BulkOperationResult;
use App\Data\User\ImpersonationSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SuperAdminUserInterface
{
    /**
     * Start impersonating a user
     */
    public function impersonateUser(User $user, int $adminId): ImpersonationSession;

    /**
     * End current impersonation session
     */
    public function endImpersonation(int $sessionId): void;

    /**
     * Perform bulk operations on multiple users
     */
    public function bulkUpdateUsers(Collection $users, array $updates, int $adminId): BulkOperationResult;

    /**
     * Get user activity report across all tenants
     */
    public function getUserActivityAcrossTenants(User $user): ActivityReport;

    /**
     * Suspend user globally across all tenants
     */
    public function suspendUserGlobally(User $user, string $reason, int $adminId): void;

    /**
     * Reactivate globally suspended user
     */
    public function reactivateUserGlobally(User $user, int $adminId): void;

    /**
     * Get all users across tenants with filtering
     */
    public function getAllUsers(array $filters = []): Collection;

    /**
     * Get users by tenant
     */
    public function getUsersByTenant(int $tenantId): Collection;

    /**
     * Get active impersonation sessions
     */
    public function getActiveImpersonationSessions(): Collection;

    /**
     * Force logout user from all sessions
     */
    public function forceLogoutUser(User $user, int $adminId): void;
}