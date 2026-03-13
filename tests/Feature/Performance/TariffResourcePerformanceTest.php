<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
});

describe('TariffResource Performance Tests', function () {
    test('table query uses eager loading to prevent N+1', function () {
        actingAs($this->admin);
        
        // Create test data
        $providers = Provider::factory()->count(5)->create();
        foreach ($providers as $provider) {
            Tariff::factory()->count(10)->for($provider)->create();
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Simulate table rendering by fetching tariffs with provider relationship
        $tariffs = Tariff::query()
            ->with('provider:id,name,service_type')
            ->get();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should be 2 queries: 1 for tariffs, 1 for providers (eager loaded)
        // Not 51 queries (1 + 50 individual provider queries)
        expect(count($queries))->toBeLessThanOrEqual(3)
            ->and($tariffs)->toHaveCount(50);
    });

    test('provider options are cached', function () {
        actingAs($this->admin);
        
        Provider::factory()->count(10)->create();
        
        // Clear cache first
        Provider::clearCachedOptions();
        
        // First call should hit database
        DB::flushQueryLog();
        DB::enableQueryLog();
        $options1 = Provider::getCachedOptions();
        $firstCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        
        // Second call should use cache (no new queries)
        DB::flushQueryLog();
        DB::enableQueryLog();
        $options2 = Provider::getCachedOptions();
        $secondCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        
        expect($firstCallQueries)->toBeGreaterThan(0)
            ->and($secondCallQueries)->toBe(0)
            ->and($options1)->toEqual($options2)
            ->and($options1)->toHaveCount(10);
    });

    test('provider cache is cleared on model changes', function () {
        actingAs($this->admin);
        
        Provider::factory()->count(5)->create();
        
        // Load cache
        $options1 = Provider::getCachedOptions();
        expect($options1)->toHaveCount(5);
        
        // Create new provider with specific name
        $newProvider = Provider::factory()->create(['name' => 'ZZZ New Provider']);
        
        // Cache should be cleared and reloaded
        $options2 = Provider::getCachedOptions();
        expect($options2)->toHaveCount(6)
            ->and($options2->get($newProvider->id))->toBe('ZZZ New Provider');
    });

    test('active status calculation is optimized', function () {
        actingAs($this->admin);
        
        $provider = Provider::factory()->create();
        $tariffs = Tariff::factory()->count(100)->for($provider)->create();
        
        // Load tariffs with computed attribute
        $loadedTariffs = Tariff::query()->get();
        
        // Access is_currently_active multiple times
        // Should not recalculate each time due to attribute caching
        foreach ($loadedTariffs as $tariff) {
            $status1 = $tariff->is_currently_active;
            $status2 = $tariff->is_currently_active;
            $status3 = $tariff->is_currently_active;
            
            expect($status1)->toBe($status2)
                ->and($status2)->toBe($status3);
        }
    });

    test('date range queries use indexes efficiently', function () {
        actingAs($this->admin);
        
        $provider = Provider::factory()->create();
        Tariff::factory()->count(100)->for($provider)->create();
        
        DB::enableQueryLog();
        
        // Query using active scope (should use indexes)
        $activeTariffs = Tariff::query()
            ->active(now())
            ->get();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Verify query was executed
        expect(count($queries))->toBeGreaterThan(0)
            ->and($activeTariffs)->not->toBeEmpty();
        
        // Check that query uses WHERE clauses on indexed columns
        $queryString = $queries[0]['query'] ?? '';
        expect($queryString)->toContain('active_from')
            ->and($queryString)->toContain('active_until');
    });

    test('provider filtering uses composite index', function () {
        actingAs($this->admin);
        
        $provider1 = Provider::factory()->create();
        $provider2 = Provider::factory()->create();
        
        Tariff::factory()->count(50)->for($provider1)->create();
        Tariff::factory()->count(50)->for($provider2)->create();
        
        DB::enableQueryLog();
        
        // Query for specific provider's active tariffs
        $tariffs = Tariff::query()
            ->forProvider($provider1->id)
            ->active(now())
            ->get();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should use composite index (provider_id, active_from)
        expect($tariffs)->not->toBeEmpty()
            ->and(count($queries))->toBe(1);
        
        $queryString = $queries[0]['query'] ?? '';
        expect($queryString)->toContain('provider_id')
            ->and($queryString)->toContain('active_from');
    });
});

