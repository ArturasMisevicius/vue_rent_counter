<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Filament\Resources\TranslationResource;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance tests for TranslationResource optimizations.
 *
 * Validates that caching and indexing improvements deliver expected performance gains:
 * - Cached language queries reduce database load
 * - Cached group filters prevent full table scans
 * - Database indexes improve query performance
 *
 * @group performance
 * @group translation
 */
class TranslationResourcePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
        ]);

        $this->actingAs($this->superadmin);
    }

    /**
     * Test that Language::getActiveLanguages() uses cache.
     *
     * PERFORMANCE: Should execute 0 queries when cache is warm.
     */
    public function test_active_languages_query_is_cached(): void
    {
        // Warm the cache
        Language::getActiveLanguages();

        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Second call should use cache
        $languages = Language::getActiveLanguages();

        $queries = DB::getQueryLog();

        $this->assertCount(0, $queries, 'Active languages should be cached (0 queries expected)');
        $this->assertNotEmpty($languages);
    }

    /**
     * Test that Translation::getDistinctGroups() uses cache.
     *
     * PERFORMANCE: Should execute 0 queries when cache is warm.
     */
    public function test_distinct_groups_query_is_cached(): void
    {
        // Create some translations
        Translation::factory()->count(10)->create();

        // Warm the cache
        Translation::getDistinctGroups();

        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Second call should use cache
        $groups = Translation::getDistinctGroups();

        $queries = DB::getQueryLog();

        $this->assertCount(0, $queries, 'Distinct groups should be cached (0 queries expected)');
        $this->assertNotEmpty($groups);
    }

    /**
     * Test that cache is invalidated when translations change.
     *
     * PERFORMANCE: Cache should be automatically cleared on save/delete.
     */
    public function test_cache_invalidation_on_translation_change(): void
    {
        // Warm the cache
        Translation::getDistinctGroups();
        $this->assertTrue(Cache::has('translations.groups'));

        // Create new translation
        Translation::factory()->create(['group' => 'new_group']);

        // Cache should be invalidated
        $this->assertFalse(Cache::has('translations.groups'));
    }

    /**
     * Test that cache is invalidated when languages change.
     *
     * PERFORMANCE: Cache should be automatically cleared on save/delete.
     */
    public function test_cache_invalidation_on_language_change(): void
    {
        // Warm the cache
        Language::getActiveLanguages();
        $this->assertTrue(Cache::has('languages.active'));

        // Update language
        $language = Language::factory()->create();
        $language->update(['is_active' => false]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has('languages.active'));
    }

    /**
     * Test form generation performance with cached languages.
     *
     * PERFORMANCE: Form should generate with minimal queries.
     */
    public function test_form_generation_uses_cached_languages(): void
    {
        // Create languages
        Language::factory()->count(5)->create(['is_active' => true]);

        // Warm the cache
        Language::getActiveLanguages();

        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Generate form schema
        $schema = TranslationResource::form(\Filament\Schemas\Schema::make());

        $queries = DB::getQueryLog();

        // Should use cached languages (0 queries for language fetch)
        $this->assertLessThanOrEqual(1, count($queries), 'Form generation should use cached languages');
    }

    /**
     * Test table filter performance with cached groups.
     *
     * PERFORMANCE: Filter options should load from cache.
     */
    public function test_table_filter_uses_cached_groups(): void
    {
        // Create translations with different groups
        Translation::factory()->count(20)->create();

        // Warm the cache
        Translation::getDistinctGroups();

        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Access filter options (simulated)
        $groups = Translation::getDistinctGroups();

        $queries = DB::getQueryLog();

        $this->assertCount(0, $queries, 'Filter options should use cached groups');
        $this->assertNotEmpty($groups);
    }

    /**
     * Test query performance with indexes on large dataset.
     *
     * PERFORMANCE: Indexed queries should complete quickly.
     */
    public function test_indexed_queries_perform_well_with_large_dataset(): void
    {
        // Create large dataset
        Translation::factory()->count(1000)->create();

        $startTime = microtime(true);

        // Query by group (indexed)
        $results = Translation::where('group', 'app')->get();

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

        $this->assertLessThan(100, $duration, 'Indexed group query should complete in < 100ms');
    }

    /**
     * Test sorting performance with indexed updated_at.
     *
     * PERFORMANCE: Sorting by updated_at should be fast with index.
     */
    public function test_sorting_by_updated_at_performs_well(): void
    {
        // Create dataset
        Translation::factory()->count(500)->create();

        $startTime = microtime(true);

        // Sort by updated_at (indexed)
        $results = Translation::orderBy('updated_at', 'desc')->limit(50)->get();

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

        $this->assertLessThan(50, $duration, 'Indexed updated_at sorting should complete in < 50ms');
        $this->assertCount(50, $results);
    }

    /**
     * Test overall resource performance with all optimizations.
     *
     * PERFORMANCE: Complete page load should be efficient.
     */
    public function test_overall_resource_performance(): void
    {
        // Setup realistic dataset
        Language::factory()->count(5)->create(['is_active' => true]);
        Translation::factory()->count(100)->create();

        // Warm caches
        Language::getActiveLanguages();
        Translation::getDistinctGroups();

        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);

        // Simulate resource operations
        $schema = TranslationResource::form(\Filament\Schemas\Schema::make());
        $groups = Translation::getDistinctGroups();
        $defaultLocale = Language::getDefault()?->code ?? 'en';

        $duration = (microtime(true) - $startTime) * 1000;
        $queries = DB::getQueryLog();

        // With caching, should have minimal queries
        $this->assertLessThanOrEqual(2, count($queries), 'Resource operations should use cached data');
        $this->assertLessThan(50, $duration, 'Overall operations should complete in < 50ms');
    }
}
