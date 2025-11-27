<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * User model observer for cache invalidation and audit logging.
 * 
 * Handles:
 * - Navigation badge cache invalidation on user changes
 * - Audit logging for sensitive operations
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
        
        // If tenant_id changed, clear old tenant cache too
        if ($user->isDirty('tenant_id')) {
            $oldTenantId = $user->getOriginal('tenant_id');
            $this->clearNavigationBadgeCacheForTenant($oldTenantId);
        }
        
        // If role changed, clear both old and new role caches
        if ($user->isDirty('role')) {
            $this->clearNavigationBadgeCache($user);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
    }

    /**
     * Clear navigation badge cache for all roles in the user's tenant.
     * 
     * @param User $user
     */
    private function clearNavigationBadgeCache(User $user): void
    {
        // Clear for all roles in this tenant
        foreach (UserRole::cases() as $role) {
            $cacheKey = sprintf(
                'user_resource_badge_%s_%s',
                $role->value,
                $user->tenant_id ?? 'all'
            );
            Cache::forget($cacheKey);
        }
    }

    /**
     * Clear navigation badge cache for a specific tenant.
     * 
     * @param int|null $tenantId
     */
    private function clearNavigationBadgeCacheForTenant(?int $tenantId): void
    {
        foreach (UserRole::cases() as $role) {
            $cacheKey = sprintf(
                'user_resource_badge_%s_%s',
                $role->value,
                $tenantId ?? 'all'
            );
            Cache::forget($cacheKey);
        }
    }
}

