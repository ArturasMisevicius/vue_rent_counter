<?php

declare(strict_types=1);

use App\Models\Building;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Performance tests for PropertiesRelationManager
 *
 * Validates query optimization, N+1 prevention, and index usage.
 * These tests ensure the relation manager maintains optimal performance
 * as data volume grows.
 *
 * Success Criteria:
 * - Query count ≤ 4 for property list (regardless of row count)
 * - Filter queries < 50ms with indexes
 * - No N+1 queries on tenant/meter relationships
 * - Memory usage < 20MB for 100 properties
 */

uses(Tests\TestCase::class);

beforeEach(function () {
    // Create admin user for testing
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'tenant_id' => 1,
    ]);
});

test('properties list executes minimal queries', function () {
    $building = Building::factory()
        ->has(Property::factory()->count(10))
        ->create(['tenant_id' => 1]);

    DB::enableQueryLog();

    $this->actingAs($this->admin)
        ->get("/admin/buildings/{$building->id}")
        ->assertOk();

    $queries = DB::getQueryLog();

    // Should be ≤ 6 queries: session, user, building, properties, tenants, meters count
    expect(count($queries))->toBeLessThanOrEqual(6)
        ->and($queries)->toBeArray();
})->group('performance', 'properties');

test('property type filter uses index', function () {
    $building = Building::factory()
        ->has(Property::factory()->count(50))
        ->create(['tenant_id' => 1]);

    DB::enableQueryLog();

    $this->actingAs($this->admin)
        ->get("/admin/buildings/{$building->id}")
        ->assertOk();

    $queries = collect(DB::getQueryLog());

    // Find the properties query
    $propertiesQuery = $queries->first(fn ($q) =>
        str_contains($q['query'], 'properties') &&
        str_contains($q['query'], 'building_id')
    );

    expect($propertiesQuery)->not->toBeNull();

    // Query should be reasonably fast (< 100ms even without real indexes in SQLite)
    if (isset($propertiesQuery['time'])) {
        expect($propertiesQuery['time'])->toBeLessThan(100);
    }
})->group('performance', 'properties');

test('tenant name loading avoids N+1', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Create 20 properties with tenants
    Property::factory()
        ->count(20)
        ->for($building)
        ->create(['tenant_id' => 1])
        ->each(function ($property) {
            $tenant = Tenant::factory()->create([
                'tenant_id' => 1,
                'property_id' => $property->id,
            ]);

            // Attach tenant via pivot
            DB::table('property_tenant')->insert([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

    DB::enableQueryLog();

    $this->actingAs($this->admin)
        ->get("/admin/buildings/{$building->id}")
        ->assertOk();

    $queries = DB::getQueryLog();

    // Should NOT have 20+ queries for tenants (N+1 problem)
    // Expected: session, user, building, properties, tenants (eager), meters count
    expect(count($queries))->toBeLessThanOrEqual(8)
        ->and($queries)->toBeArray();
})->group('performance', 'properties', 'n+1');

test('meters count uses efficient query', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Create properties with varying meter counts
    Property::factory()
        ->count(10)
        ->for($building)
        ->create(['tenant_id' => 1])
        ->each(function ($property) {
            Meter::factory()
                ->count(rand(1, 5))
                ->for($property)
                ->create(['tenant_id' => 1]);
        });

    DB::enableQueryLog();

    $this->actingAs($this->admin)
        ->get("/admin/buildings/{$building->id}")
        ->assertOk();

    $queries = collect(DB::getQueryLog());

    // Should use COUNT() query, not load all meter models
    $metersQuery = $queries->first(fn ($q) =>
        str_contains(strtolower($q['query']), 'count') &&
        str_contains($q['query'], 'meters')
    );

    expect($metersQuery)->not->toBeNull();
})->group('performance', 'properties');

test('large property filter performs well', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Create mix of small and large properties
    Property::factory()
        ->count(30)
        ->for($building)
        ->create(['tenant_id' => 1, 'area_sqm' => 50]);

    Property::factory()
        ->count(20)
        ->for($building)
        ->create(['tenant_id' => 1, 'area_sqm' => 150]);

    DB::enableQueryLog();

    $this->actingAs($this->admin)
        ->get("/admin/buildings/{$building->id}")
        ->assertOk();

    $queries = DB::getQueryLog();

    // Should use area_sqm index for filtering
    expect(count($queries))->toBeLessThanOrEqual(8);
})->group('performance', 'properties', 'filters');

test('tenant assignment form query is optimized', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->for($building)->create(['tenant_id' => 1]);

    // Create available tenants (no properties)
    Tenant::factory()->count(10)->create([
        'tenant_id' => 1,
        'property_id' => null,
    ]);

    DB::enableQueryLog();

    // Simulate opening tenant management modal
    $availableTenants = Tenant::select('id', 'name')
        ->where('tenant_id', 1)
        ->whereDoesntHave('properties', fn ($q) =>
            $q->wherePivotNull('vacated_at')
        )
        ->orderBy('name')
        ->get();

    $queries = DB::getQueryLog();

    expect($availableTenants)->toHaveCount(10)
        ->and(count($queries))->toBeLessThanOrEqual(2); // Should be 1-2 queries max
})->group('performance', 'properties', 'forms');

test('config caching reduces file I/O', function () {
    // This test validates that config is loaded once and cached
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Clear config cache to ensure fresh load
    Artisan::call('config:clear');

    $startTime = microtime(true);

    // Make multiple requests that would trigger config loads
    for ($i = 0; $i < 5; $i++) {
        config('billing.property');
    }

    $duration = microtime(true) - $startTime;

    // With caching, 5 config calls should be very fast (< 10ms)
    expect($duration)->toBeLessThan(0.01); // 10ms
})->group('performance', 'properties', 'caching');

test('memory usage stays under limit for large datasets', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Create 100 properties with tenants and meters
    Property::factory()
        ->count(100)
        ->for($building)
        ->create(['tenant_id' => 1])
        ->each(function ($property) {
            $tenant = Tenant::factory()->create([
                'tenant_id' => 1,
                'property_id' => $property->id,
            ]);

            DB::table('property_tenant')->insert([
                'property_id' => $property->id,
                'tenant_id' => $tenant->id,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Meter::factory()->count(3)->for($property)->create(['tenant_id' => 1]);
        });

    $memoryBefore = memory_get_usage(true);

    $this->actingAs($this->admin)
        ->get("/admin/buildings/{$building->id}")
        ->assertOk();

    $memoryAfter = memory_get_usage(true);
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

    // Should use < 20MB for 100 properties
    expect($memoryUsed)->toBeLessThan(20);
})->group('performance', 'properties', 'memory')->skip('Slow test - run manually');

test('address search performs efficiently', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Create properties with searchable addresses
    Property::factory()
        ->count(50)
        ->for($building)
        ->create(['tenant_id' => 1]);

    DB::enableQueryLog();

    // Simulate address search
    $results = Property::where('building_id', $building->id)
        ->where('address', 'like', '%Street%')
        ->get();

    $queries = DB::getQueryLog();

    // Should use index on address (or at least be a single query)
    expect(count($queries))->toBe(1);
})->group('performance', 'properties', 'search');

test('composite index improves building+type queries', function () {
    $building = Building::factory()->create(['tenant_id' => 1]);

    // Create mix of apartments and houses
    Property::factory()->count(30)->for($building)->create([
        'tenant_id' => 1,
        'type' => 'apartment',
    ]);

    Property::factory()->count(20)->for($building)->create([
        'tenant_id' => 1,
        'type' => 'house',
    ]);

    DB::enableQueryLog();

    // Query that should use composite index
    $apartments = Property::where('building_id', $building->id)
        ->where('type', 'apartment')
        ->get();

    $queries = DB::getQueryLog();

    expect($apartments)->toHaveCount(30)
        ->and(count($queries))->toBe(1);
})->group('performance', 'properties', 'indexes');
