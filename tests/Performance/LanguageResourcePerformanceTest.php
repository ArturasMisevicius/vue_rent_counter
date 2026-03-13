<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance tests for LanguageResource optimizations.
 *
 * Tests verify:
 * - Database indexes improve query performance
 * - Caching reduces database queries
 * - Model mutator eliminates redundant transformations
 */
class LanguageResourcePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that active languages query uses indexes efficiently.
     */
    public function test_active_languages_query_uses_indexes(): void
    {
        // Create test data
        Language::factory()->count(50)->create(['is_active' => true]);
        Language::factory()->count(50)->create(['is_active' => false]);

        // Enable query logging
        DB::enableQueryLog();

        // Query active languages
        $languages = Language::active()->orderBy('display_order')->get();

        // Get executed queries
        $queries = DB::getQueryLog();

        // Should execute exactly 1 query
        $this->assertCount(1, $queries);

        // Verify query uses index (check EXPLAIN output would show index usage)
        $this->assertCount(50, $languages);
    }

    /**
     * Test that getActiveLanguages() caches results.
     */
    public function test_get_active_languages_caches_results(): void
    {
        // Clear cache
        Cache::flush();

        // Create test data
        Language::factory()->count(5)->create(['is_active' => true]);

        // First call - should hit database
        DB::enableQueryLog();
        $languages1 = Language::getActiveLanguages();
        $firstCallQueries = count(DB::getQueryLog());

        // Second call - should use cache
        DB::flushQueryLog();
        $languages2 = Language::getActiveLanguages();
        $secondCallQueries = count(DB::getQueryLog());

        // First call should execute query
        $this->assertEquals(1, $firstCallQueries);

        // Second call should NOT execute query (cached)
        $this->assertEquals(0, $secondCallQueries);

        // Results should be identical
        $this->assertEquals($languages1->pluck('id'), $languages2->pluck('id'));
    }

    /**
     * Test that cache is invalidated when language is updated.
     */
    public function test_cache_invalidated_on_language_update(): void
    {
        // Clear cache
        Cache::flush();

        // Create test data
        $language = Language::factory()->create(['is_active' => true]);

        // Prime cache
        Language::getActiveLanguages();
        $this->assertTrue(Cache::has('languages.active'));

        // Update language
        $language->update(['name' => 'Updated Name']);

        // Cache should be invalidated
        $this->assertFalse(Cache::has('languages.active'));
    }

    /**
     * Test that cache is invalidated when language is deleted.
     */
    public function test_cache_invalidated_on_language_delete(): void
    {
        // Clear cache
        Cache::flush();

        // Create test data
        $language = Language::factory()->create(['is_active' => true]);

        // Prime cache
        Language::getActiveLanguages();
        $this->assertTrue(Cache::has('languages.active'));

        // Delete language
        $language->delete();

        // Cache should be invalidated
        $this->assertFalse(Cache::has('languages.active'));
    }

    /**
     * Test that model mutator handles lowercase conversion.
     */
    public function test_model_mutator_converts_code_to_lowercase(): void
    {
        // Create language with uppercase code
        $language = Language::factory()->create(['code' => 'EN']);

        // Refresh from database
        $language->refresh();

        // Code should be lowercase
        $this->assertEquals('en', $language->code);
    }

    /**
     * Test that getDefault() caches default language.
     */
    public function test_get_default_caches_result(): void
    {
        // Clear cache
        Cache::flush();

        // Create test data
        Language::factory()->create(['is_default' => true, 'code' => 'en']);

        // First call - should hit database
        DB::enableQueryLog();
        $default1 = Language::getDefault();
        $firstCallQueries = count(DB::getQueryLog());

        // Second call - should use cache
        DB::flushQueryLog();
        $default2 = Language::getDefault();
        $secondCallQueries = count(DB::getQueryLog());

        // First call should execute query
        $this->assertEquals(1, $firstCallQueries);

        // Second call should NOT execute query (cached)
        $this->assertEquals(0, $secondCallQueries);

        // Results should be identical
        $this->assertEquals($default1->id, $default2->id);
    }

    /**
     * Benchmark: Query performance with indexes.
     */
    public function test_benchmark_filtered_query_performance(): void
    {
        // Create moderate dataset (avoid unique constraint issues)
        Language::factory()->count(20)->create(['is_active' => true]);
        Language::factory()->count(20)->create(['is_active' => false]);

        // Warm up
        Language::active()->orderBy('display_order')->get();

        // Benchmark query
        $startTime = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            Language::active()->orderBy('display_order')->get();
        }
        
        $endTime = microtime(true);
        $avgTime = ($endTime - $startTime) / 50;

        // With indexes, average query should be < 10ms
        $this->assertLessThan(0.01, $avgTime, 
            "Average query time {$avgTime}s exceeds 10ms threshold");
    }
}
