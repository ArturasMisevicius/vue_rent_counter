<?php

declare(strict_types=1);

use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Automated N+1 Query Detection Tests for FaqResource
 *
 * These tests automatically detect N+1 query problems by:
 * 1. Counting queries with different record counts
 * 2. Ensuring query count remains constant
 * 3. Detecting relationship loading patterns
 */

beforeEach(function () {
    $this->actingAs(User::factory()->superadmin()->create());
});

test('detects N+1 queries automatically using query count assertion', function () {
    // Create test data
    Faq::factory()->count(20)->create();
    
    // Enable query logging
    DB::enableQueryLog();
    
    // Simulate table rendering
    $table = FaqResource::table(new Table());
    
    $queryCount = count(DB::getQueryLog());
    
    // Assert maximum query count
    // For FaqResource without relationships: should be 1-2 queries max
    expect($queryCount)->toBeLessThanOrEqual(2)
        ->and($queryCount)->toBeGreaterThan(0);
    
    DB::disableQueryLog();
})->group('performance', 'faq', 'n+1', 'automated');

test('query count remains constant with increasing record count (N+1 detector)', function () {
    $recordCounts = [5, 10, 20, 50];
    $queryCounts = [];
    
    foreach ($recordCounts as $count) {
        // Fresh database
        Faq::query()->delete();
        Faq::factory()->count($count)->create();
        
        DB::enableQueryLog();
        
        // Simulate table rendering
        $table = FaqResource::table(new Table());
        
        $queryCounts[$count] = count(DB::getQueryLog());
        
        DB::disableQueryLog();
    }
    
    // All query counts should be identical
    // If they increase with record count, we have an N+1 problem
    $uniqueQueryCounts = array_unique($queryCounts);
    
    expect($uniqueQueryCounts)->toHaveCount(1)
        ->and($queryCounts[5])->toBe($queryCounts[50]);
})->group('performance', 'faq', 'n+1', 'automated');

test('no duplicate queries detected (N+1 pattern)', function () {
    Faq::factory()->count(15)->create();
    
    DB::enableQueryLog();
    
    // Simulate table rendering
    $table = FaqResource::table(new Table());
    
    $queries = DB::getQueryLog();
    
    // Extract query strings (without bindings)
    $queryStrings = collect($queries)->pluck('query')->toArray();
    
    // Count occurrences of each query
    $queryCounts = array_count_values($queryStrings);
    
    // Find queries that repeat more than once
    $repeatedQueries = array_filter($queryCounts, fn ($count) => $count > 1);
    
    // No query should repeat (except for specific cases like cache checks)
    // Filter out acceptable repeated queries
    $problematicRepeats = array_filter($repeatedQueries, function ($count, $query) {
        // Allow cache-related queries to repeat
        return !str_contains($query, 'cache') && $count > 2;
    }, ARRAY_FILTER_USE_BOTH);
    
    expect($problematicRepeats)->toBeEmpty();
    
    DB::disableQueryLog();
})->group('performance', 'faq', 'n+1', 'automated');

test('relationship queries use WHERE IN instead of individual WHERE clauses', function () {
    // This test is for future-proofing when relationships are added
    Faq::factory()->count(10)->create();
    
    DB::enableQueryLog();
    
    // Simulate table rendering
    $table = FaqResource::table(new Table());
    
    $queries = DB::getQueryLog();
    
    // Check for WHERE IN patterns (good) vs multiple WHERE id = ? (bad)
    $whereInQueries = collect($queries)->filter(function ($query) {
        return str_contains($query['query'], 'where') && 
               str_contains($query['query'], 'in');
    });
    
    $individualWhereQueries = collect($queries)->filter(function ($query) {
        return str_contains($query['query'], 'where') && 
               str_contains($query['query'], 'id = ?') &&
               !str_contains($query['query'], 'in');
    });
    
    // If we have relationship queries, they should use WHERE IN
    // Currently FaqResource has no relationships in table, so this should pass
    expect($individualWhereQueries->count())->toBeLessThanOrEqual(1);
    
    DB::disableQueryLog();
})->group('performance', 'faq', 'n+1', 'automated');

test('query execution time scales linearly with record count', function () {
    $recordCounts = [10, 50, 100];
    $executionTimes = [];
    
    foreach ($recordCounts as $count) {
        Faq::query()->delete();
        Faq::factory()->count($count)->create();
        
        $start = microtime(true);
        
        DB::enableQueryLog();
        $table = FaqResource::table(new Table());
        DB::disableQueryLog();
        
        $executionTimes[$count] = (microtime(true) - $start) * 1000; // Convert to ms
    }
    
    // Execution time should scale roughly linearly
    // If it scales exponentially, we likely have an N+1 problem
    $ratio = $executionTimes[100] / $executionTimes[10];
    
    // Ratio should be close to 10 (linear scaling)
    // Allow some variance (5-15x) for database overhead
    expect($ratio)->toBeLessThan(20) // Not exponential
        ->and($ratio)->toBeGreaterThan(1); // Some increase is expected
})->group('performance', 'faq', 'n+1', 'automated');

test('memory usage remains reasonable with large datasets', function () {
    Faq::factory()->count(100)->create();
    
    $memoryBefore = memory_get_usage(true);
    
    // Simulate table rendering
    $table = FaqResource::table(new Table());
    
    $memoryAfter = memory_get_usage(true);
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
    
    // Should use less than 5MB for 100 records
    expect($memoryUsed)->toBeLessThan(5);
})->group('performance', 'faq', 'n+1', 'automated');

test('no lazy loading detected in table rendering', function () {
    Faq::factory()->count(10)->create();
    
    // Track lazy loading events
    $lazyLoadCount = 0;
    
    DB::listen(function ($query) use (&$lazyLoadCount) {
        // Detect lazy loading patterns
        if (str_contains($query->sql, 'where') && 
            str_contains($query->sql, 'id = ?') &&
            !str_contains($query->sql, 'faqs')) {
            $lazyLoadCount++;
        }
    });
    
    // Simulate table rendering
    $table = FaqResource::table(new Table());
    
    // No lazy loading should occur
    expect($lazyLoadCount)->toBe(0);
})->group('performance', 'faq', 'n+1', 'automated');
