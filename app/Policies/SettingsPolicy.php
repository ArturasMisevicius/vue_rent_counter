<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TenantBoundaryService;

final readonly class SettingsPolicy
{
    public function __construct(
        private TenantBoundaryService $tenantBoundaryService
    ) {}

    /**
     * Determine whether the user can view settings.
     */
    public function viewSettings(User $user): bool
    {
        return $this->tenantBoundaryService->canPerformAdminOperations($user);
    }

    /**
     * Determine whether the user can update settings.
     */
    public function updateSettings(User $user): bool
    {
        return $this->tenantBoundaryService->canPerformAdminOperations($user);
    }

    /**
     * Determine whether the user can run backups.
     */
    public function runBackup(User $user): bool
    {
        return $this->tenantBoundaryService->canPerformAdminOperations($user);
    }

    /**
     * Determine whether the user can clear cache.
     */
    public function clearCache(User $user): bool
    {
        return $this->tenantBoundaryService->canPerformAdminOperations($user);
    }
}
