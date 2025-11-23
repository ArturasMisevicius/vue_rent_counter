<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Manages the current tenant (organization) context throughout the application
 */
class TenantContext
{
    private const SESSION_KEY = 'tenant_id';
    private const CACHE_PREFIX = 'tenant:';
    private const CACHE_TTL = 3600; // 1 hour

    private static ?Organization $currentTenant = null;
    private static bool $initialized = false;

    /**
     * Initialize tenant context from session or user
     */
    public static function initialize(): void
    {
        if (static::$initialized) {
            return;
        }

        $tenantId = Session::get(static::SESSION_KEY);

        if ($tenantId) {
            static::$currentTenant = static::loadTenant($tenantId);
        } elseif (auth()->check()) {
            static::setFromUser(auth()->user());
        }

        static::$initialized = true;
    }

    /**
     * Set tenant context from authenticated user
     */
    public static function setFromUser(User $user): void
    {
        if ($user->isSuperadmin()) {
            // Superadmin can switch tenants, don't auto-set
            return;
        }

        if ($user->tenant_id) {
            static::set($user->tenant_id);
        }
    }

    /**
     * Set the current tenant
     */
    public static function set(int $tenantId): void
    {
        $tenant = static::loadTenant($tenantId);

        if (!$tenant) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }

        if (!$tenant->isActive()) {
            throw new \RuntimeException("Tenant {$tenantId} is not active");
        }

        static::$currentTenant = $tenant;
        Session::put(static::SESSION_KEY, $tenantId);

        // Record activity
        $tenant->recordActivity();
    }

    /**
     * Get the current tenant
     */
    public static function get(): ?Organization
    {
        if (!static::$initialized) {
            static::initialize();
        }

        return static::$currentTenant;
    }

    /**
     * Get the current tenant ID
     */
    public static function id(): ?int
    {
        return static::get()?->id;
    }

    /**
     * Check if tenant context is set
     */
    public static function has(): bool
    {
        return static::get() !== null;
    }

    /**
     * Clear tenant context
     */
    public static function clear(): void
    {
        static::$currentTenant = null;
        static::$initialized = false;
        Session::forget(static::SESSION_KEY);
    }

    /**
     * Switch to a different tenant (for superadmin)
     */
    public static function switch(int $tenantId): void
    {
        if (!auth()->check() || !auth()->user()->isSuperadmin()) {
            throw new \RuntimeException('Only superadmin can switch tenants');
        }

        static::set($tenantId);
    }

    /**
     * Execute callback within tenant context
     */
    public static function within(int $tenantId, callable $callback): mixed
    {
        $previousTenant = static::$currentTenant;
        $previousInitialized = static::$initialized;

        try {
            static::set($tenantId);
            return $callback();
        } finally {
            static::$currentTenant = $previousTenant;
            static::$initialized = $previousInitialized;
            
            if ($previousTenant) {
                Session::put(static::SESSION_KEY, $previousTenant->id);
            } else {
                Session::forget(static::SESSION_KEY);
            }
        }
    }

    /**
     * Load tenant from database with caching
     */
    private static function loadTenant(int $tenantId): ?Organization
    {
        return Cache::remember(
            static::CACHE_PREFIX . $tenantId,
            static::CACHE_TTL,
            fn () => Organization::find($tenantId)
        );
    }

    /**
     * Forget cached tenant
     */
    public static function forgetCache(int $tenantId): void
    {
        Cache::forget(static::CACHE_PREFIX . $tenantId);
    }

    /**
     * Check if user can access tenant
     */
    public static function canAccess(User $user, int $tenantId): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->tenant_id === $tenantId;
    }

    /**
     * Validate tenant context for current user
     */
    public static function validate(): void
    {
        if (!auth()->check()) {
            throw new \RuntimeException('User not authenticated');
        }

        $user = auth()->user();

        if ($user->isSuperadmin()) {
            return; // Superadmin can access any tenant
        }

        if (!static::has()) {
            throw new \RuntimeException('Tenant context not set');
        }

        if (!static::canAccess($user, static::id())) {
            throw new \RuntimeException('User cannot access this tenant');
        }
    }
}
