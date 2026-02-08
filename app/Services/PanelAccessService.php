<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Facades\Cache;

/**
 * Panel Access Service - Centralized Filament Panel Authorization
 * 
 * Handles all panel access logic with clear separation of concerns.
 * Provides caching and performance optimizations for frequent access checks.
 */
class PanelAccessService
{
    private const CACHE_TTL = 1800; // 30 minutes
    private const CACHE_PREFIX = 'panel_access:';
    
    // Panel identifiers
    public const ADMIN_PANEL = 'admin';
    public const TENANT_PANEL = 'tenant';
    
    // Roles allowed for admin panel
    private const ADMIN_PANEL_ROLES = [
        UserRole::ADMIN,
        UserRole::MANAGER,
        UserRole::SUPERADMIN,
    ];

    public function __construct(
        private readonly UserRoleService $userRoleService
    ) {}

    /**
     * Check if user can access a specific panel.
     */
    public function canAccessPanel(User $user, Panel $panel): bool
    {
        $cacheKey = $this->getCacheKey($user->id, $panel->getId());
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $panel) {
            return $this->performPanelAccessCheck($user, $panel);
        });
    }

    /**
     * Check if user can access the admin panel.
     */
    public function canAccessAdminPanel(User $user): bool
    {
        if (!$this->isUserActive($user)) {
            return false;
        }

        return $this->userRoleService->hasRole($user, self::ADMIN_PANEL_ROLES);
    }

    /**
     * Check if user can access tenant-specific panels.
     */
    public function canAccessTenantPanel(User $user): bool
    {
        if (!$this->isUserActive($user)) {
            return false;
        }

        return $this->userRoleService->isTenant($user);
    }

    /**
     * Check if user can access superadmin-only panels.
     */
    public function canAccessSuperadminPanel(User $user): bool
    {
        if (!$this->isUserActive($user)) {
            return false;
        }

        return $this->userRoleService->isSuperadmin($user);
    }

    /**
     * Get all panels accessible by the user.
     */
    public function getAccessiblePanels(User $user): array
    {
        $panels = [];

        if ($this->canAccessAdminPanel($user)) {
            $panels[] = self::ADMIN_PANEL;
        }

        if ($this->canAccessTenantPanel($user)) {
            $panels[] = self::TENANT_PANEL;
        }

        return $panels;
    }

    /**
     * Clear panel access cache for a user.
     */
    public function clearPanelAccessCache(User $user): void
    {
        // For Redis cache, use pattern matching
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $pattern = self::CACHE_PREFIX . $user->id . ':*';
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
            return;
        }

        // For other cache stores, clear specific known keys
        $panels = [self::ADMIN_PANEL, self::TENANT_PANEL, 'custom', 'superadmin'];

        foreach ($panels as $panel) {
            $key = self::CACHE_PREFIX . "{$user->id}:{$panel}";
            Cache::forget($key);
        }
    }

    /**
     * Perform the actual panel access check.
     */
    private function performPanelAccessCheck(User $user, Panel $panel): bool
    {
        // Ensure user is active (prevents deactivated accounts from accessing panels)
        if (!$this->isUserActive($user)) {
            return false;
        }

        return match ($panel->getId()) {
            self::ADMIN_PANEL => $this->userRoleService->hasRole($user, self::ADMIN_PANEL_ROLES),
            self::TENANT_PANEL => $this->userRoleService->isTenant($user),
            default => $this->userRoleService->isSuperadmin($user), // Other panels: Only SUPERADMIN
        };
    }

    /**
     * Check if user account is active.
     */
    private function isUserActive(User $user): bool
    {
        return $user->is_active && $user->suspended_at === null;
    }

    /**
     * Generate cache key for panel access checks.
     */
    private function getCacheKey(int $userId, string $panelId): string
    {
        return self::CACHE_PREFIX . "{$userId}:{$panelId}";
    }
}