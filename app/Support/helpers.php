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

if (! function_exists('enum_label')) {
    /**
     * Resolve a translated label from an enum instance or backed value.
     */
    function enum_label(mixed $value, ?string $enumClass = null): string
    {
        if ($value instanceof \UnitEnum) {
            if (method_exists($value, 'label')) {
                return $value->label();
            }

            return $value instanceof \BackedEnum ? (string) $value->value : $value->name;
        }

        if ($enumClass && enum_exists($enumClass)) {
            $enum = $enumClass::tryFrom((string) $value);

            if ($enum && method_exists($enum, 'label')) {
                return $enum->label();
            }
        }

        return is_string($value) ? $value : (string) $value;
    }
}
