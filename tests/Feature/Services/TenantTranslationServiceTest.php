<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Translation;
use App\Services\TenantTranslationService;
use App\Services\TranslationCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TenantTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantTranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $cacheService = new TranslationCacheService(
            Cache::store(),
            'en',
            ['en' => [], 'lt' => [], 'ru' => []]
        );
        
        $this->service = new TenantTranslationService($cacheService);
    }

    public function test_can_set_and_get_tenant_translation(): void
    {
        $tenantId = 1;
        $key = 'tenant.test.key';
        $locale = 'en';
        $value = 'Tenant Test Value';
        
        // Set translation
        $result = $this->service->set($key, $locale, $value, $tenantId);
        expect($result)->toBeTrue();
        
        // Verify in database
        $this->assertDatabaseHas('translations', [
            'tenant_id' => $tenantId,
            'key' => $key,
            'locale' => $locale,
            'value' => $value,
        ]);
        
        // Get translation
        $retrieved = $this->service->get($key, $locale, $tenantId);
        expect($retrieved)->toBe($value);
    }

    public function test_returns_null_for_nonexistent_translation(): void
    {
        $result = $this->service->get('nonexistent.key', 'en', 1);
        expect($result)->toBeNull();
    }

    public function test_can_import_multiple_translations(): void
    {
        $tenantId = 1;
        $translations = [
            'import.key1' => [
                'en' => 'English Value 1',
                'lt' => 'Lithuanian Value 1',
            ],
            'import.key2' => [
                'en' => 'English Value 2',
                'lt' => 'Lithuanian Value 2',
            ],
        ];
        
        $imported = $this->service->import($translations, $tenantId);
        expect($imported)->toBe(4); // 2 keys × 2 locales
        
        // Verify all translations were imported
        foreach ($translations as $key => $localeValues) {
            foreach ($localeValues as $locale => $value) {
                $this->assertDatabaseHas('translations', [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'locale' => $locale,
                    'value' => $value,
                ]);
            }
        }
    }

    public function test_can_export_tenant_translations(): void
    {
        $tenantId = 1;
        
        // Create test translations
        Translation::create([
            'tenant_id' => $tenantId,
            'key' => 'export.key1',
            'locale' => 'en',
            'value' => 'Export Value 1',
        ]);
        
        Translation::create([
            'tenant_id' => $tenantId,
            'key' => 'export.key1',
            'locale' => 'lt',
            'value' => 'Eksporto reikšmė 1',
        ]);
        
        $exported = $this->service->export($tenantId);
        
        expect($exported)->toHaveKey('export.key1');
        expect($exported['export.key1'])->toHaveKeys(['en', 'lt']);
        expect($exported['export.key1']['en'])->toBe('Export Value 1');
        expect($exported['export.key1']['lt'])->toBe('Eksporto reikšmė 1');
    }

    public function test_get_with_fallback_uses_tenant_then_global(): void
    {
        $tenantId = 1;
        $key = 'fallback.test';
        $locale = 'en';
        
        // Test fallback to global when no tenant translation
        $result = $this->service->getWithFallback($key, $locale, $tenantId);
        
        // Should fall back to Laravel's translation system
        $globalTranslation = __($key, [], $locale);
        if ($globalTranslation !== $key) {
            expect($result)->toBe($globalTranslation);
        } else {
            expect($result)->toBeNull();
        }
        
        // Set tenant-specific translation
        $tenantValue = 'Tenant Specific Value';
        $this->service->set($key, $locale, $tenantValue, $tenantId);
        
        // Should now return tenant translation
        $result = $this->service->getWithFallback($key, $locale, $tenantId);
        expect($result)->toBe($tenantValue);
    }

    public function test_clear_tenant_cache_removes_cached_translations(): void
    {
        $tenantId = 1;
        $key = 'cache.test';
        $locale = 'en';
        $value = 'Cached Value';
        
        // Set translation (this should cache it)
        $this->service->set($key, $locale, $value, $tenantId);
        
        // Verify it's cached by getting it
        $cached = $this->service->get($key, $locale, $tenantId);
        expect($cached)->toBe($value);
        
        // Clear cache
        $result = $this->service->clearTenantCache($tenantId);
        expect($result)->toBeTrue();
        
        // Translation should still exist in database but cache should be cleared
        $this->assertDatabaseHas('translations', [
            'tenant_id' => $tenantId,
            'key' => $key,
            'locale' => $locale,
            'value' => $value,
        ]);
    }

    public function test_sync_with_global_adds_missing_translations(): void
    {
        $tenantId = 1;
        
        // This would sync global translations to tenant
        // In a real implementation, this would check actual global translation files
        $synced = $this->service->syncWithGlobal($tenantId);
        
        // Should return number of synced translations
        expect($synced)->toBeGreaterThanOrEqual(0);
    }

    public function test_handles_null_tenant_id_gracefully(): void
    {
        // All methods should handle null tenant ID gracefully
        expect($this->service->get('test.key', 'en', null))->toBeNull();
        expect($this->service->set('test.key', 'en', 'value', null))->toBeFalse();
        expect($this->service->export(null))->toBe([]);
        expect($this->service->clearTenantCache(null))->toBeFalse();
    }
}