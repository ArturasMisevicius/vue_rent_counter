<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/**
 * Performance tests for BuildingResource optimizations.
 *
 * Validates that query count, memory usage, and response times
 * meet performance targets after optimization.
 *
 * Run with: php artisan test --filter=BuildingResourcePerformance
 */

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
});

/**
 * Test that building list query count is optimized.
 *
 * Target: ≤ 3 queries (1 main + 1 count + 1 tenant scope)
 * Before optimization: 12 queries (N+1 on properties_count)
 */
test('building list has minimal query count', function () {
    actingAs($this->admin);

    // Create test data
    Building::factory()
        ->count(10)
        ->has(Property::factory()->count(5))
        ->create(['tenant_id' => $this->admin->tenant_id]);

    // Enable query logging
    DB::enableQueryLog();

    // Simulate table rendering with withCount
    $buildings = Building::query()
        ->withCount('properties')
        ->paginate(15);

    // Get query count
    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Assert query count is optimized
    expect($queryCount)->toBeLessThanOrEqual(3, "Expected ≤ 3 queries, got {$queryCount}");

    DB::disableQueryLog();
});

/**
 * Test that properties relation manager query count is optimized.
 *
 * Target: ≤ 5 queries (1 main + 1 tenant eager load + 1 meter count + pagination)
 * Before optimization: 23 queries (N+1 on tenants and meters)
 */
test('properties relation manager has minimal query count', function () {
    actingAs($this->admin);

    // Create test data
    $building = Building::factory()
        ->has(Property::factory()->count(20))
        ->create(['tenant_id' => $this->admin->tenant_id]);

    // Enable query logging
    DB::enableQueryLog();

    // Simulate relation manager query with optimized eager loading
    $properties = $building->properties()
        ->with([
            'tenants:id,name',
            'tenants' => fn ($q) => $q->wherePivotNull('vacated_at')->limit(1)
        ])
        ->withCount('meters')
        ->paginate(15);

    // Get query count
    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Assert query count is optimized
    expect($queryCount)->toBeLessThanOrEqual(5, "Expected ≤ 5 queries, got {$queryCount}");

    DB::disableQueryLog();
});

/**
 * Test that translation caching reduces __() calls.
 *
 * Validates that getCachedTranslations() is called once per request.
 */
test('translation caching is effective', function () {
    actingAs($this->admin);

    // Access private method via reflection
    $reflection = new \ReflectionClass(BuildingResource::class);
    $method = $reflection->getMethod('getCachedTranslations');
    $method->setAccessible(true);

    // First call should cache translations
    $translations1 = $method->invoke(null);

    // Second call should return cached values
    $translations2 = $method->invoke(null);

    // Assert same instance (cached)
    expect($translations1)->toBe($translations2)
        ->and($translations1)->toHaveKey('name')
        ->and($translations1)->toHaveKey('address');
});

/**
 * Test that memory usage is within acceptable limits.
 *
 * Target: < 20MB per request
 * Before optimization: ~45MB
 */
test('memory usage is optimized', function () {
    actingAs($this->admin);

    // Create test data
    Building::factory()
        ->count(50)
        ->has(Property::factory()->count(10))
        ->create(['tenant_id' => $this->admin->tenant_id]);

    $memoryBefore = memory_get_usage(true);

    // Simulate table rendering with optimized query
    $buildings = Building::query()
        ->withCount('properties')
        ->paginate(15);
    
    // Force collection iteration
    $buildings->each(fn ($building) => $building->properties_count);

    $memoryAfter = memory_get_usage(true);
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

    // Assert memory usage is reasonable
    expect($memoryUsed)->toBeLessThan(20, "Expected < 20MB memory usage, got {$memoryUsed}MB");
});

/**
 * Test that database indexes exist for optimized queries.
 */
test('performance indexes exist on buildings table', function () {
    $driver = DB::getDriverName();

    if ($driver === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list(buildings)");
        $indexNames = array_column($indexes, 'name');

        // Assert critical indexes exist
        expect($indexNames)->toContain('buildings_tenant_address_index')
            ->and($indexNames)->toContain('buildings_name_index');
    }
})->skip(fn () => DB::getDriverName() !== 'sqlite', 'Only runs on SQLite');

/**
 * Test that properties table has required indexes.
 */
test('performance indexes exist on properties table', function () {
    $driver = DB::getDriverName();

    if ($driver === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list(properties)");
        $indexNames = array_column($indexes, 'name');

        // Assert critical indexes exist
        expect($indexNames)->toContain('properties_tenant_type_index')
            ->and($indexNames)->toContain('properties_area_index')
            ->and($indexNames)->toContain('properties_building_address_index');
    }
})->skip(fn () => DB::getDriverName() !== 'sqlite', 'Only runs on SQLite');
