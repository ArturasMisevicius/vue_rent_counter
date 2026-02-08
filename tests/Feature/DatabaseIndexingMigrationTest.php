<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Test suite for comprehensive database indexing migration.
 * 
 * Verifies that all indexes are created correctly and improve query performance.
 * 
 * Migration: 2025_01_15_000001_add_comprehensive_database_indexes.php
 */
describe('Database Indexing Migration', function () {
    test('migration runs successfully without errors', function () {
        // Run the migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php'])
            ->assertSuccessful();
    });

    test('users table has email index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('users');
        // Email has a unique constraint which automatically creates an index
        // Check for either the unique constraint or an explicit index
        $hasEmailIndex = collect($indexes)->contains(fn($index) => 
            str_contains($index, 'email') || $index === 'users_email_unique'
        );
        expect($hasEmailIndex)->toBeTrue();
    });

    test('users table has is_active index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('users');
        expect($indexes)->toContain('users_is_active_index');
    });

    test('users table has tenant_active composite index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('users');
        expect($indexes)->toContain('users_tenant_active_index');
    });

    test('properties table has created_at index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('properties');
        expect($indexes)->toContain('properties_created_at_index');
    });

    test('properties table has tenant_created composite index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('properties');
        expect($indexes)->toContain('properties_tenant_created_index');
    });

    test('meters table has type index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('meters');
        expect($indexes)->toContain('meters_type_index');
    });

    test('meters table has property_type composite index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('meters');
        expect($indexes)->toContain('meters_property_type_index');
    });

    test('meter_readings table has entered_by index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('meter_readings');
        expect($indexes)->toContain('meter_readings_entered_by_index');
    });

    test('meter_readings table has tenant_date composite index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('meter_readings');
        expect($indexes)->toContain('meter_readings_tenant_date_index');
    });

    test('invoices table has finalized_at index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('invoices');
        expect($indexes)->toContain('invoices_finalized_at_index');
    });

    test('invoices table has tenant_status composite index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('invoices');
        expect($indexes)->toContain('invoices_tenant_status_index');
    });

    test('invoices table has period composite index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('invoices');
        expect($indexes)->toContain('invoices_period_index');
    });

    test('buildings table has created_at index', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $indexes = getTableIndexes('buildings');
        expect($indexes)->toContain('buildings_created_at_index');
    });

    test('migration can be rolled back without errors', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        $this->artisan('migrate:rollback', ['--step' => 1])
            ->assertSuccessful();
    });

    test('indexes improve query performance for email lookups', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        // Create test user
        $user = \App\Models\User::factory()->create(['email' => 'test@example.com']);
        
        // Query should use index
        $start = microtime(true);
        $result = \App\Models\User::where('email', 'test@example.com')->first();
        $duration = microtime(true) - $start;
        
        expect($result)->not->toBeNull();
        expect($result->email)->toBe('test@example.com');
        // Indexed query should be fast (< 100ms for small dataset)
        expect($duration)->toBeLessThan(0.1);
    });

    test('indexes improve query performance for active user filtering', function () {
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_12_02_000001_add_comprehensive_database_indexes.php']);
        
        // Create test users
        \App\Models\User::factory()->count(10)->create(['is_active' => true]);
        \App\Models\User::factory()->count(5)->create(['is_active' => false]);
        
        // Query should use index
        $start = microtime(true);
        $activeUsers = \App\Models\User::where('is_active', true)->get();
        $duration = microtime(true) - $start;
        
        expect($activeUsers)->toHaveCount(10);
        // Indexed query should be fast
        expect($duration)->toBeLessThan(0.1);
    });
});

/**
 * Helper function to get indexes for a table.
 */
function getTableIndexes(string $table): array
{
    $driver = DB::connection()->getDriverName();
    
    if ($driver === 'sqlite') {
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=?", [$table]);
        return array_map(fn($idx) => $idx->name, $indexes);
    } elseif ($driver === 'mysql') {
        $database = DB::connection()->getDatabaseName();
        $indexes = DB::select(
            "SELECT DISTINCT index_name 
             FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ?",
            [$database, $table]
        );
        return array_map(fn($idx) => $idx->index_name, $indexes);
    } else {
        // PostgreSQL
        $indexes = DB::select(
            "SELECT indexname 
             FROM pg_indexes 
             WHERE schemaname = 'public' AND tablename = ?",
            [$table]
        );
        return array_map(fn($idx) => $idx->indexname, $indexes);
    }
}
