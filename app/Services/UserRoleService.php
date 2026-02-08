<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * User Role Service - Centralized Role Management
 * 
 * Handles all role-related operations with caching and performance optimizations.
 * Provides a clean interface for role checking, capability validation, and
 * schema-aware role management.
 */
class UserRoleService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'user_role:';

    /**
     * Check if user has a specific role with caching.
     */
    public function hasRole(User $user, string|UserRole|array $roles, ?string $guard = null): bool
    {
        $cacheKey = $this->getCacheKey($user->id, 'has_role', $roles, $guard);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $roles, $guard) {
            return $this->performRoleCheck($user, $roles, $guard);
        });
    }

    /**
     * Check if user can access admin features.
     */
    public function canAccessAdmin(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::SUPERADMIN,
        ]);
    }

    /**
     * Check if user is a superadmin.
     */
    public function isSuperadmin(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Check if user is an admin (property owner).
     */
    public function isAdmin(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Check if user is a manager (legacy admin role).
     */
    public function isManager(User $user): bool
    {
        return $user->role === UserRole::MANAGER;
    }

    /**
     * Check if user is a tenant (apartment resident).
     */
    public function isTenant(User $user): bool
    {
        return $user->role === UserRole::TENANT;
    }

    /**
     * Get user's role priority for ordering.
     */
    public function getRolePriority(User $user): int
    {
        return match ($user->role) {
            UserRole::SUPERADMIN => 1,
            UserRole::ADMIN => 2,
            UserRole::MANAGER => 3,
            UserRole::TENANT => 4,
            default => 5,
        };
    }

    /**
     * Check if user has administrative privileges.
     */
    public function hasAdministrativePrivileges(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
        ], true);
    }

    /**
     * Clear role cache for a user.
     */
    public function clearRoleCache(User $user): void
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
        $operations = ['has_role'];
        $roles = ['admin', 'manager', 'tenant', 'superadmin'];
        $guards = ['default', 'web'];

        foreach ($operations as $operation) {
            foreach ($roles as $role) {
                foreach ($guards as $guard) {
                    $key = self::CACHE_PREFIX . "{$user->id}:{$operation}:{$role}:{$guard}";
                    Cache::forget($key);
                }
            }
        }
    }

    /**
     * Perform the actual role check with schema awareness.
     */
    private function performRoleCheck(User $user, string|UserRole|array $roles, ?string $guard): bool
    {
        $normalizedRoles = collect(Arr::wrap($roles))
            ->map(fn ($role) => $this->normalizeRoleToString($role))
            ->values();

        $userRole = $user->role instanceof UserRole
            ? $user->role->value
            : $this->normalizeRoleToString($user->role);

        // If Spatie permission tables exist, prefer the trait method.
        // Fall back to enum/string role checks when pivots are not populated.
        if ($this->hasPermissionTables()) {
            if ($user->hasRoleTrait($roles, $guard)) {
                return true;
            }
        }

        return $normalizedRoles->contains($userRole);
    }

    /**
     * Check if permission tables exist in the database.
     */
    private function hasPermissionTables(): bool
    {
        static $hasPermissionTables = null;
        
        if ($hasPermissionTables === null) {
            $hasPermissionTables = Schema::hasTable(config('permission.table_names.model_has_roles'));
        }
        
        return $hasPermissionTables;
    }

    /**
     * Safely normalize any role type to a string representation.
     * Handles BackedEnums, Spatie Role objects, arrays, and other complex types.
     */
    private function normalizeRoleToString(mixed $role): string
    {
        if ($role instanceof \BackedEnum) {
            return $role->value;
        }
        
        if (is_object($role)) {
            // Handle Spatie Role objects or other objects
            if (method_exists($role, 'getName')) {
                return $role->getName();
            }
            if (method_exists($role, 'name')) {
                return $role->name;
            }
            if (isset($role->name)) {
                return $role->name;
            }
            // Fallback to class name for objects
            return class_basename($role);
        }
        
        if (is_array($role)) {
            // Handle nested arrays by JSON encoding and hashing for cache safety
            return md5(json_encode($role));
        }
        
        if ($role === null) {
            return 'null';
        }
        
        return (string) $role;
    }

    /**
     * Generate cache key for role checks.
     */
    private function getCacheKey(int $userId, string $operation, mixed $roles = null, ?string $guard = null): string
    {
        if (is_array($roles)) {
            $roleKey = collect($roles)
                ->map(fn ($role) => $this->normalizeRoleToString($role))
                ->implode(',');
        } else {
            $roleKey = $this->normalizeRoleToString($roles);
        }
        
        $guardKey = $guard ?? 'default';
        
        return self::CACHE_PREFIX . "{$userId}:{$operation}:{$roleKey}:{$guardKey}";
    }
}
