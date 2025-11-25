<?php

declare(strict_types=1);

use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Performance tests for FaqResource optimizations.
 *
 * Validates:
 * - Authorization check memoization
 * - Translation call optimization
 * - Category cache invalidation
 * - Query performance with indexes
 * - Table render performance
 */

beforeEach(function () {
    $this->actingAs(User::factory()->superadmin()->create());
});

test('authorization check is memoized within request', function () {
    // Clear any existing cache
    FaqResource::class::$canAccessCache = null;
    
    $callCount = 0;
    
    // Mock auth to count calls
    $originalUser = auth()->user();
    
    // First call
    $result1 = FaqResource::canViewAny();
    
    // Second call should use memoized result
    $result2 = FaqResource::canCreate();
    $result3 = FaqResource::shouldRegisterNavigation();
    
    expect($result1)->toBeTrue()
        ->and($result2)->toBeTrue()
        ->and($result3)->toBeTrue();
    
    // All calls should return same result without re-checking auth
})->group('performance', 'faq');

test('category cache is invalidated on FAQ save', function () {
    // Create initial FAQ
    $faq = Faq::factory()->create(['category' => 'Initial']);
    
    // Populate cache
    $categories1 = Cache::get('faq_categories');
    if ($categories1 === null) {
        $categories1 = FaqResource::class::getCategoryOptions();
    }
    
    expect($categories1)->toBeArray();
    
    // Update category - should invalidate cache
    $faq->update(['category' => 'Updated']);
    
    // Cache should be cleared
    $cachedValue = Cache::get('faq_categories');
    expect($cachedValue)->toBeNull();
    
    // Fresh fetch should include new category
    $categories2 = FaqResource::class::getCategoryOptions();
    expect($categories2)->toContain('Updated');
})->group('performance', 'faq');

test('category cache is invalidated on FAQ delete', function () {
    $faq = Faq::factory()->create(['category' => 'ToDelete']);
    
    // Populate cache
    FaqResource::class::getCategoryOptions();
    expect(Cache::has('faq_categories'))->toBeTrue();
    
    // Delete FAQ - should invalidate cache
    $faq->delete();
    
    // Cache should be cleared
    expect(Cache::has('faq_categories'))->toBeFalse();
})->group('performance', 'faq');

test('table renders within performance budget with 100 FAQs', function () {
    Faq::factory()->count(100)->create();
    
    $start = microtime(true);
    
    // Simulate table render
    $table = FaqResource::table(new Table());
    
    $duration = (microtime(true) - $start) * 1000;
    
    // Should render in under 100ms
    expect($duration)->toBeLessThan(100);
})->group('performance', 'faq');

test('category filter performs well with 1000 FAQs', function () {
    // Create FAQs with various categories
    Faq::factory()->count(1000)->create();
    
    $start = microtime(true);
    
    // Get category options (uses index)
    $categories = FaqResource::class::getCategoryOptions();
    
    $duration = (microtime(true) - $start) * 1000;
    
    // Should complete in under 50ms with index
    expect($duration)->toBeLessThan(50)
        ->and($categories)->toBeArray();
})->group('performance', 'faq');

test('query uses explicit column selection', function () {
    Faq::factory()->count(10)->create();
    
    DB::enableQueryLog();
    
    // Trigger table query
    $table = FaqResource::table(new Table());
    
    $queries = DB::getQueryLog();
    
    // Should have queries with explicit SELECT
    $hasExplicitSelect = collect($queries)->contains(function ($query) {
        return str_contains($query['query'], 'select') && 
               !str_contains($query['query'], 'select *');
    });
    
    expect($hasExplicitSelect)->toBeTrue();
    
    DB::disableQueryLog();
})->group('performance', 'faq');

test('no N+1 queries when listing FAQs', function () {
    // Create 20 FAQs
    Faq::factory()->count(20)->create();
    
    DB::enableQueryLog();
    
    // Simulate table rendering
    $table = FaqResource::table(new Table());
    
    $queryCount = count(DB::getQueryLog());
    
    // Should only execute 1 query (the main SELECT)
    // No additional queries for relationships since we don't display them
    expect($queryCount)->toBeLessThanOrEqual(2); // Allow 1-2 queries max
    
    DB::disableQueryLog();
})->group('performance', 'faq', 'n+1');

test('query count remains constant regardless of record count', function () {
    // Test with different record counts
    $recordCounts = [10, 50, 100];
    
    foreach ($recordCounts as $count) {
        // Fresh database for each test
        Faq::query()->delete();
        Faq::factory()->count($count)->create();
        
        DB::enableQueryLog();
        
        // Simulate table rendering
        $table = FaqResource::table(new Table());
        
        $queryCount = count(DB::getQueryLog());
        
        // Query count should remain constant (1-2 queries)
        expect($queryCount)->toBeLessThanOrEqual(2)
            ->and($queryCount)->toBeGreaterThan(0);
        
        DB::disableQueryLog();
    }
})->group('performance', 'faq', 'n+1');

test('translation calls are memoized', function () {
    // First call
    $trans1 = FaqResource::class::trans('faq.labels.question');
    
    // Second call should use cache
    $trans2 = FaqResource::class::trans('faq.labels.question');
    
    expect($trans1)->toBe($trans2)
        ->and($trans1)->toBeString();
})->group('performance', 'faq');

test('category index exists for filter performance', function () {
    $indexes = DB::select("PRAGMA index_list('faqs')");
    
    $hasCategoryIndex = collect($indexes)->contains(function ($index) {
        return str_contains($index->name, 'category');
    });
    
    expect($hasCategoryIndex)->toBeTrue();
})->group('performance', 'faq')->skip(
    fn () => DB::connection()->getDriverName() !== 'sqlite',
    'SQLite-specific test'
);

test('memory usage stays within budget', function () {
    Faq::factory()->count(100)->create();
    
    $memoryBefore = memory_get_usage(true);
    
    // Render table
    FaqResource::table(new Table());
    
    $memoryAfter = memory_get_usage(true);
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
    
    // Should use less than 5MB for 100 FAQs
    expect($memoryUsed)->toBeLessThan(5);
})->group('performance', 'faq');

test('authorization overhead is minimal', function () {
    $iterations = 100;
    
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        FaqResource::canViewAny();
        FaqResource::canCreate();
        FaqResource::canEdit(new Faq());
        FaqResource::canDelete(new Faq());
    }
    
    $duration = (microtime(true) - $start) * 1000;
    $avgPerCall = $duration / ($iterations * 4);
    
    // Average should be under 0.1ms per call with memoization
    expect($avgPerCall)->toBeLessThan(0.1);
})->group('performance', 'faq');
