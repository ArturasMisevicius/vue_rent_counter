<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Language;
use App\Models\Subscription;
use App\Models\Tariff;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Group;

/**
 * TariffControllerPerformanceTest
 * 
 * Performance tests for TariffController to ensure query optimization
 * and prevent N+1 query issues.
 * 
 * Coverage:
 * - N+1 query prevention in index
 * - Eager loading verification
 * - Query count assertions
 * - Memory usage optimization
 * 
 * Requirements:
 * - Performance optimization per docs/performance/TARIFF_CONTROLLER_PERFORMANCE_OPTIMIZATION.md
 * 
 * @package Tests\Performance
 */
#[Group('performance')]
#[Group('tariffs')]
class TariffControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Provider $provider;

    /**
     * Format a slice of the DB query log for assertion output.
     *
     * @param  array<int, array{query:string, bindings:array<int, mixed>, time:float}>  $queries
     */
    private function formatQueriesForOutput(array $queries): string
    {
        return collect($queries)
            ->map(function (array $entry, int $index): string {
                $sql = $entry['query'] ?? '';
                $bindings = $entry['bindings'] ?? [];
                $time = $entry['time'] ?? 0.0;

                $bindingsText = json_encode($bindings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return sprintf('[%d] %s | bindings=%s | time=%sms', $index + 1, $sql, $bindingsText, $time);
            })
            ->implode(PHP_EOL);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        Subscription::factory()->active()->create(['user_id' => $this->admin->id]);
        $this->provider = Provider::factory()->create();

        // Warm up caches used by global middleware/view composers so performance tests
        // measure controller/query behavior rather than first-hit cache misses.
        app(SubscriptionChecker::class)->getSubscription($this->admin);
        Language::getActiveLanguages();
        app(PermissionRegistrar::class)->getPermissions();
    }

    /**
     * Test: Index method prevents N+1 queries with eager loading.
     * 
     * Expected: 3 queries total (session, user, tariffs with provider)
     * - 1 query for session/auth
     * - 1 query for user
     * - 1 query for tariffs with eager loaded providers
     */
    public function test_index_prevents_n_plus_one_queries(): void
    {
        $this->actingAs($this->admin);

        // Create 20 tariffs to test pagination
        Tariff::factory()->count(20)->create(['provider_id' => $this->provider->id]);

        // Enable query logging
        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $response = $this->get(route('admin.tariffs.index'));

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;
        $queries = array_slice(DB::getQueryLog(), $queryCountBefore, $queriesExecuted);

        $response->assertOk();

        // Should execute exactly 2 queries:
        // 1. SELECT tariffs with pagination
        // 2. SELECT providers (eager loaded in single query)
        // Note: Session/auth queries happen before our measurement
        $this->assertLessThanOrEqual(
            3,
            $queriesExecuted,
            'Index should execute at most 3 queries (tariffs + providers + count). Actual: ' . $queriesExecuted . PHP_EOL . $this->formatQueriesForOutput($queries),
        );
    }

    /**
     * Test: Index with 100 tariffs maintains query efficiency.
     * 
     * Verifies that query count doesn't scale with number of records.
     */
    public function test_index_query_count_does_not_scale_with_records(): void
    {
        $this->actingAs($this->admin);

        // Create 100 tariffs across multiple providers
        $providers = Provider::factory()->count(5)->create();
        foreach ($providers as $provider) {
            Tariff::factory()->count(20)->create(['provider_id' => $provider->id]);
        }

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $response = $this->get(route('admin.tariffs.index'));

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;
        $queries = array_slice(DB::getQueryLog(), $queryCountBefore, $queriesExecuted);

        $response->assertOk();

        // Query count should remain constant regardless of total records
        $this->assertLessThanOrEqual(
            3,
            $queriesExecuted,
            'Query count should not scale with number of records. Actual: ' . $queriesExecuted . PHP_EOL . $this->formatQueriesForOutput($queries),
        );
    }

    /**
     * Test: Show method eager loads provider efficiently.
     */
    public function test_show_eager_loads_provider(): void
    {
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $response = $this->get(route('admin.tariffs.show', $tariff));

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        $response->assertOk();

        // Should execute minimal queries:
        // 1. Load tariff (if not already loaded)
        // 2. Load provider (if not already loaded)
        // 3. Load version history
        $this->assertLessThanOrEqual(4, $queriesExecuted,
            'Show should execute at most 4 queries. Actual: ' . $queriesExecuted
        );
    }

    /**
     * Test: Version history query is limited and optimized.
     */
    public function test_version_history_is_limited(): void
    {
        $this->actingAs($this->admin);

        // Create 15 versions of the same tariff
        $tariffs = Tariff::factory()->count(15)->create([
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
        ]);

        $latestTariff = $tariffs->last();

        $response = $this->get(route('admin.tariffs.show', $latestTariff));

        $response->assertOk();
        $response->assertViewHas('versionHistory', function ($history) {
            // Version history should be limited to 10 records
            return $history->count() <= 10;
        });
    }

    /**
     * Test: Index with sorting maintains query efficiency.
     */
    public function test_index_with_sorting_maintains_efficiency(): void
    {
        $this->actingAs($this->admin);

        Tariff::factory()->count(20)->create(['provider_id' => $this->provider->id]);

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $response = $this->get(route('admin.tariffs.index', [
            'sort' => 'name',
            'direction' => 'asc',
        ]));

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;
        $queries = array_slice(DB::getQueryLog(), $queryCountBefore, $queriesExecuted);

        $response->assertOk();

        // Sorting should not add additional queries
        $this->assertLessThanOrEqual(
            3,
            $queriesExecuted,
            'Sorting should not increase query count. Actual: ' . $queriesExecuted . PHP_EOL . $this->formatQueriesForOutput($queries),
        );
    }

    /**
     * Test: Create form loads providers efficiently.
     */
    public function test_create_form_loads_providers_efficiently(): void
    {
        $this->actingAs($this->admin);

        // Create multiple providers
        Provider::factory()->count(10)->create();

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $response = $this->get(route('admin.tariffs.create'));

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        $response->assertOk();

        // Should execute only 1 query for providers
        $this->assertLessThanOrEqual(2, $queriesExecuted,
            'Create form should execute at most 2 queries. Actual: ' . $queriesExecuted
        );
    }

    /**
     * Test: Edit form loads data efficiently.
     */
    public function test_edit_form_loads_data_efficiently(): void
    {
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);
        Provider::factory()->count(10)->create();

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $response = $this->get(route('admin.tariffs.edit', $tariff));

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;
        $queries = array_slice(DB::getQueryLog(), $queryCountBefore, $queriesExecuted);

        $response->assertOk();

        // Should execute minimal queries:
        // 1. Load tariff (if needed)
        // 2. Load tariff's provider (if needed)
        // 3. Load all providers for dropdown
        $this->assertLessThanOrEqual(
            4,
            $queriesExecuted,
            'Edit form should execute at most 4 queries. Actual: ' . $queriesExecuted . PHP_EOL . $this->formatQueriesForOutput($queries),
        );
    }
}
