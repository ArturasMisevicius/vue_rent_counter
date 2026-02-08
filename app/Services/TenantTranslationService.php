<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant Translation Service
 * 
 * Handles tenant-specific translations and dynamic translation management
 * for multi-tenant applications with customizable content.
 */
final readonly class TenantTranslationService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private TranslationCacheService $cacheService,
    ) {}

    /**
     * Get translation for current tenant
     */
    public function get(string $key, string $locale, ?int $tenantId = null): ?string
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return null;
        }

        $cacheKey = "tenant.{$tenantId}.{$key}";
        
        return $this->cacheService->remember(
            $cacheKey,
            $locale,
            fn() => $this->fetchTenantTranslation($key, $locale, $tenantId)
        );
    }

    /**
     * Set translation for tenant
     */
    public function set(string $key, string $locale, string $value, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return false;
        }

        // Store in database
        $translation = Translation::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'key' => $key,
                'locale' => $locale,
            ],
            [
                'value' => $value,
            ]
        );

        // Update cache
        $cacheKey = "tenant.{$tenantId}.{$key}";
        $this->cacheService->put($cacheKey, $locale, $value);

        return $translation->exists;
    }

    /**
     * Get all translations for tenant
     */
    public function getAllForTenant(?int $tenantId = null): Collection
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return collect();
        }

        return Cache::remember(
            "tenant.{$tenantId}.all_translations",
            self::CACHE_TTL,
            fn() => Translation::where('tenant_id', $tenantId)->get()
        );
    }

    /**
     * Import translations for tenant
     */
    public function import(array $translations, ?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return 0;
        }

        $imported = 0;
        
        foreach ($translations as $key => $localeValues) {
            foreach ($localeValues as $locale => $value) {
                if ($this->set($key, $locale, $value, $tenantId)) {
                    $imported++;
                }
            }
        }

        // Clear tenant cache
        $this->clearTenantCache($tenantId);

        return $imported;
    }

    /**
     * Export translations for tenant
     */
    public function export(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return [];
        }

        $translations = $this->getAllForTenant($tenantId);
        $exported = [];

        foreach ($translations as $translation) {
            $exported[$translation->key][$translation->locale] = $translation->value;
        }

        return $exported;
    }

    /**
     * Clear cache for tenant
     */
    public function clearTenantCache(?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return false;
        }

        // Clear specific tenant cache
        Cache::forget("tenant.{$tenantId}.all_translations");
        
        // Clear individual translation caches
        $translations = Translation::where('tenant_id', $tenantId)->get(['key', 'locale']);
        
        foreach ($translations as $translation) {
            $cacheKey = "tenant.{$tenantId}.{$translation->key}";
            $this->cacheService->forget($cacheKey, $translation->locale);
        }

        return true;
    }

    /**
     * Get translation fallback chain
     */
    public function getWithFallback(string $key, string $locale, ?int $tenantId = null): ?string
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        // Try tenant-specific translation first
        if ($tenantId) {
            $tenantTranslation = $this->get($key, $locale, $tenantId);
            if ($tenantTranslation) {
                return $tenantTranslation;
            }
        }

        // Fall back to global translation
        $globalTranslation = __($key, [], $locale);
        
        return $globalTranslation !== $key ? $globalTranslation : null;
    }

    /**
     * Sync tenant translations with global translations
     */
    public function syncWithGlobal(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        
        if (!$tenantId) {
            return 0;
        }

        // Get all global translation keys
        $globalKeys = $this->getGlobalTranslationKeys();
        $synced = 0;

        foreach ($globalKeys as $key) {
            foreach (config('locales.available', []) as $locale => $config) {
                $globalValue = __($key, [], $locale);
                
                if ($globalValue !== $key) {
                    // Check if tenant has this translation
                    $tenantValue = $this->get($key, $locale, $tenantId);
                    
                    if (!$tenantValue) {
                        $this->set($key, $locale, $globalValue, $tenantId);
                        $synced++;
                    }
                }
            }
        }

        return $synced;
    }

    /**
     * Fetch tenant translation from database
     */
    private function fetchTenantTranslation(string $key, string $locale, int $tenantId): ?string
    {
        $translation = Translation::where([
            'tenant_id' => $tenantId,
            'key' => $key,
            'locale' => $locale,
        ])->first();

        return $translation?->value;
    }

    /**
     * Get current tenant ID from context
     */
    private function getCurrentTenantId(): ?int
    {
        // This would integrate with your tenant context system
        if (class_exists(\App\Services\TenantContext::class)) {
            return \App\Services\TenantContext::getCurrentTenantId();
        }

        return session('tenant_id');
    }

    /**
     * Get all global translation keys
     */
    private function getGlobalTranslationKeys(): array
    {
        // This is a simplified implementation
        // In practice, you'd scan translation files or maintain a registry
        return [
            'common.english',
            'common.lithuanian',
            'common.russian',
            'common.language',
            'auth.failed',
            'auth.password',
            'auth.throttle',
            'validation.required',
            'validation.email',
            'validation.min.string',
        ];
    }
}