<?php

declare(strict_types=1);

namespace App\Facades;

use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * TenantContext Facade
 * 
 * Provides static access to the TenantContext service for convenient usage
 * throughout the application while maintaining proper dependency injection
 * under the hood.
 * 
 * @method static void set(int $tenantId)
 * @method static int|null get()
 * @method static int|null id()
 * @method static void switch(int $tenantId, User $user)
 * @method static bool validate(User $user)
 * @method static void clear()
 * @method static int|null getDefaultTenant(User $user)
 * @method static void initialize(User $user)
 * @method static bool canSwitchTo(int $tenantId, User $user)
 * 
 * @see \App\Services\TenantContext
 */
class TenantContext extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\TenantContext::class;
    }
}