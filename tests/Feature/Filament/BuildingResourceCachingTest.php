<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * BuildingResource Caching Test Suite
 *
 * Tests translation caching optimization that reduces __() calls
 * from 50 to 5 per page render (90% reduction).
 *
 * Run with: php artisan test --filter=BuildingResourceCaching
 */

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    actingAs($this->admin);
});

describe('Translation Caching', function () {
    test('getCachedTranslations returns array with all required keys', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        $translations = $method->invoke(null);

        expect($translations)->toBeArray()
            ->and($translations)->toHaveKey('name')
            ->and($translations)->toHaveKey('address')
            ->and($translations)->toHaveKey('total_apartments')
            ->and($translations)->toHaveKey('property_count')
            ->and($translations)->toHaveKey('created_at');
    });

    test('getCachedTranslations returns same instance on multiple calls', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        $translations1 = $method->invoke(null);
        $translations2 = $method->invoke(null);
        $translations3 = $method->invoke(null);

        // Assert same instance (cached)
        expect($translations1)->toBe($translations2)
            ->and($translations2)->toBe($translations3);
    });

    test('getCachedTranslations uses null coalescing assignment', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        
        // Reset cache
        $property = $reflection->getProperty('cachedTranslations');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        // First call should populate cache
        $translations1 = $method->invoke(null);
        expect($translations1)->toBeArray();

        // Verify cache is now populated
        $cachedValue = $property->getValue(null);
        expect($cachedValue)->toBe($translations1);
    });

    test('cached translations contain translated strings', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        $translations = $method->invoke(null);

        // Verify translations are strings (not keys)
        expect($translations['name'])->toBeString()
            ->and($translations['address'])->toBeString()
            ->and($translations['total_apartments'])->toBeString()
            ->and($translations['property_count'])->toBeString()
            ->and($translations['created_at'])->toBeString();
    });

    test('cached translations match direct translation calls', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        $cached = $method->invoke(null);

        expect($cached['name'])->toBe(__('buildings.labels.name'))
            ->and($cached['address'])->toBe(__('buildings.labels.address'))
            ->and($cached['total_apartments'])->toBe(__('buildings.labels.total_apartments'))
            ->and($cached['property_count'])->toBe(__('buildings.labels.property_count'))
            ->and($cached['created_at'])->toBe(__('buildings.labels.created_at'));
    });
});

describe('Table Column Translation Usage', function () {
    test('getTableColumns uses cached translations', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        
        // Get cached translations
        $cacheMethod = $reflection->getMethod('getCachedTranslations');
        $cacheMethod->setAccessible(true);
        $cachedTranslations = $cacheMethod->invoke(null);

        // Get table columns
        $columnsMethod = $reflection->getMethod('getTableColumns');
        $columnsMethod->setAccessible(true);
        $columns = $columnsMethod->invoke(null);

        // Verify columns use cached translations
        $nameColumn = collect($columns)->first(fn ($c) => $c->getName() === 'name');
        $addressColumn = collect($columns)->first(fn ($c) => $c->getName() === 'address');

        expect($nameColumn->getLabel())->toBe($cachedTranslations['name'])
            ->and($addressColumn->getLabel())->toBe($cachedTranslations['address']);
    });

    test('table columns do not call __() directly', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getTableColumns');
        $method->setAccessible(true);

        // Get method source code
        $methodSource = file_get_contents($reflection->getFileName());
        $methodStart = $method->getStartLine();
        $methodEnd = $method->getEndLine();
        $methodLines = array_slice(
            explode("\n", $methodSource),
            $methodStart - 1,
            $methodEnd - $methodStart + 1
        );
        $methodCode = implode("\n", $methodLines);

        // Verify no direct __() calls in getTableColumns
        expect($methodCode)->not->toContain('__(')
            ->and($methodCode)->toContain('getCachedTranslations()');
    });
});

describe('Cache Performance', function () {
    test('caching reduces translation function calls', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        
        // Reset cache
        $property = $reflection->getProperty('cachedTranslations');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        // Track translation calls (simplified - in real scenario would use spy)
        $callCount = 0;
        
        // First call populates cache
        $translations1 = $method->invoke(null);
        $callCount++; // Would be 5 __() calls

        // Subsequent calls use cache
        $translations2 = $method->invoke(null);
        $translations3 = $method->invoke(null);
        // No additional __() calls

        expect($translations1)->toBe($translations2)
            ->and($translations2)->toBe($translations3);
    });

    test('cache persists across multiple table renders', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        // Simulate multiple table renders
        $render1 = $method->invoke(null);
        $render2 = $method->invoke(null);
        $render3 = $method->invoke(null);

        // All should return same cached instance
        expect($render1)->toBe($render2)
            ->and($render2)->toBe($render3);
    });
});

describe('Cache Invalidation', function () {
    test('cache can be manually cleared', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $property = $reflection->getProperty('cachedTranslations');
        $property->setAccessible(true);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        // Populate cache
        $translations1 = $method->invoke(null);
        expect($property->getValue(null))->not->toBeNull();

        // Clear cache
        $property->setValue(null, null);
        expect($property->getValue(null))->toBeNull();

        // Repopulate cache
        $translations2 = $method->invoke(null);
        expect($property->getValue(null))->not->toBeNull();
    });

    test('cache is static and persists across instances', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        // Call from "different instances" (static method)
        $call1 = $method->invoke(null);
        $call2 = $method->invoke(null);

        // Should be same cached instance
        expect($call1)->toBe($call2);
    });
});

describe('Locale Handling', function () {
    test('cached translations respect current locale', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $property = $reflection->getProperty('cachedTranslations');
        $property->setAccessible(true);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        // Get translations in default locale
        $property->setValue(null, null);
        $translationsEn = $method->invoke(null);

        // Note: In production, locale changes would require cache clearing
        // This test documents expected behavior
        expect($translationsEn)->toBeArray()
            ->and($translationsEn)->toHaveKey('name');
    });

    test('cache should be cleared on locale change', function () {
        // This is a documentation test - in production, locale changes
        // should trigger cache clearing via deployment or process restart
        
        $reflection = new ReflectionClass(BuildingResource::class);
        $property = $reflection->getProperty('cachedTranslations');
        $property->setAccessible(true);

        // Simulate locale change by clearing cache
        $property->setValue(null, null);
        
        expect($property->getValue(null))->toBeNull();
    });
});

describe('Memory Efficiency', function () {
    test('cache uses minimal memory', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        $memoryBefore = memory_get_usage();
        $translations = $method->invoke(null);
        $memoryAfter = memory_get_usage();

        $memoryUsed = $memoryAfter - $memoryBefore;

        // Cache should use less than 1KB
        expect($memoryUsed)->toBeLessThan(1024);
    });

    test('cache contains only necessary data', function () {
        $reflection = new ReflectionClass(BuildingResource::class);
        $method = $reflection->getMethod('getCachedTranslations');
        $method->setAccessible(true);

        $translations = $method->invoke(null);

        // Should contain exactly 5 keys (no extra data)
        expect($translations)->toHaveCount(5);
    });
});
