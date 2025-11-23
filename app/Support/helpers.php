<?php

use App\Models\Organization;
use App\Services\TenantContext;

if (! function_exists('tenant')) {
    /**
     * Get the current tenant organization from the tenant context or session.
     */
    function tenant(): ?Organization
    {
        return TenantContext::get();
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Get the current tenant ID from the tenant context or authenticated user.
     */
    function tenant_id(): ?int
    {
        return TenantContext::id();
    }
}
