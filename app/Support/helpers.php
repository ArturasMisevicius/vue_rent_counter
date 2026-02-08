<?php

use App\Models\Organization;
use App\Services\TenantContext as TenantContextService;

if (! function_exists('tenant')) {
    /**
     * Get the current tenant organization from the tenant context or session.
     */
    function tenant(): ?Organization
    {
        // Prevent circular reference during bootstrap
        if (!app()->bound(TenantContextService::class)) {
            return null;
        }
        
        try {
            return app(TenantContextService::class)->get();
        } catch (\Throwable $e) {
            // Prevent errors during bootstrap
            return null;
        }
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Get the current tenant ID from the tenant context or authenticated user.
     */
    function tenant_id(): ?int
    {
        // Prevent circular reference during bootstrap
        if (!app()->bound(TenantContextService::class)) {
            return null;
        }
        
        try {
            return app(TenantContextService::class)->id();
        } catch (\Throwable $e) {
            // Prevent errors during bootstrap
            return null;
        }
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

if (! function_exists('svgIcon')) {
    /**
     * Render minimal SVG icons mapped by key for landing page features.
     *
     * Uses Heroicons via blade-heroicons package for maintainability.
     * Icons are cached by the blade-icons package automatically.
     *
     * @deprecated Use @svg() directive or <x-icon> component directly in Blade templates
     */
    function svgIcon(string $key): string
    {
        try {
            $iconType = \App\Enums\IconType::fromLegacyKey($key);
            return svg($iconType->heroicon(), 'h-5 w-5')->toHtml();
        } catch (\Throwable $e) {
            // Fallback to default icon if specific icon not found
            try {
                return svg(\App\Enums\IconType::DEFAULT->heroicon(), 'h-5 w-5')->toHtml();
            } catch (\Throwable $e) {
                // Ultimate fallback
                return '<svg class="h-5 w-5"><rect width="20" height="20" fill="currentColor"/></svg>';
            }
        }
    }
}
