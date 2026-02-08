<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * TenantContext service for managing tenant context throughout the application.
 * Works directly with Session and Auth, no external dependencies required.
 */
final class TenantContext
{
    private const SESSION_KEY = "tenant_context";

    public function get(): ?int
    {
        // First check session
        $tenantId = Session::get(self::SESSION_KEY);
        
        if ($tenantId && is_int($tenantId) && $tenantId > 0) {
            return $tenantId;
        }
        
        // If not in session, get from authenticated user
        $user = Auth::user();
        if ($user && isset($user->tenant_id) && $user->tenant_id > 0) {
            return $user->tenant_id;
        }
        
        return null;
    }
    
    public function id(): ?int
    {
        return $this->get();
    }
    
    public function has(): bool
    {
        return $this->get() !== null;
    }
    
    public function set(int $tenantId): void
    {
        if ($tenantId > 0) {
            Session::put(self::SESSION_KEY, $tenantId);
        }
    }
    
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
    
    public function initialize(): void
    {
        // Initialize tenant context from authenticated user if not already set
        if (!$this->has()) {
            $user = Auth::user();
            if ($user && isset($user->tenant_id) && $user->tenant_id > 0) {
                $this->set($user->tenant_id);
            }
        }
    }

    /**
     * Get the current tenant ID from the tenant context.
     * Alias for get() method to maintain compatibility with TenantBoundaryService
     */
    public function getCurrentTenantId(): ?int
    {
        return $this->get();
    }
}
