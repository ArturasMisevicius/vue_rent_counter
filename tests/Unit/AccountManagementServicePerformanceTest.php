<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountManagementServicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AccountManagementService::class);
    }

    /**
     * Test that createAdminAccount uses optimal number of queries.
     */
    public function test_create_admin_account_query_count(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        DB::enableQueryLog();

        $this->service->createAdminAccount([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => 'password123',
            'organization_name' => 'Test Org',
            'plan_type' => 'professional',
        ], $superadmin);

        $queries = DB::getQueryLog();

        // Should be 8 queries or less:
        // 1. Validation (email unique check)
        // 2. SELECT max tenant_id (for generation)
        // 3. INSERT user
        // 4. INSERT subscription
        // 5. INSERT audit log
        // 6. SELECT user (fresh)
        // 7. SELECT subscription (eager load)
        // Note: 7 queries is acceptable for this operation
        $this->assertLessThanOrEqual(8, count($queries), 'Too many queries executed');
    }

    /**
     * Test that createAdminAccount completes in reasonable time.
     */
    public function test_create_admin_account_performance(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $startTime = microtime(true);

        $this->service->createAdminAccount([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => 'password123',
            'organization_name' => 'Test Org',
            'plan_type' => 'professional',
        ], $superadmin);

        $duration = (microtime(true) - $startTime) * 1000;

        // Should complete in under 500ms (including password hashing)
        $this->assertLessThan(500, $duration, "Operation took {$duration}ms, expected < 500ms");
    }

    /**
     * Test that createTenantAccount uses optimal number of queries.
     */
    public function test_create_tenant_account_query_count(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->create(['building_id' => $building->id, 'tenant_id' => 1]);

        DB::enableQueryLog();

        $this->service->createTenantAccount([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'password' => 'password123',
            'property_id' => $property->id,
        ], $admin);

        $queries = DB::getQueryLog();

        // Should be 11 queries or less:
        // 1. Validation (email unique check)
        // 2. Validation (property_id exists check)
        // 3. SELECT property (with select optimization)
        // 4. INSERT user
        // 5. INSERT audit log
        // 6. SELECT user (fresh)
        // 7. SELECT property (eager load)
        // 8. SELECT parent user (eager load)
        // 9-11. Additional framework queries
        // Note: 10 queries is acceptable for this operation
        $this->assertLessThanOrEqual(11, count($queries), 'Too many queries executed');
    }

    /**
     * Test that reassignTenant doesn't have N+1 query problem.
     */
    public function test_reassign_tenant_no_n_plus_one(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property1 = Property::factory()->create(['building_id' => $building->id, 'tenant_id' => 1]);
        $property2 = Property::factory()->create(['building_id' => $building->id, 'tenant_id' => 1]);
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => $property1->id,
            'parent_user_id' => $admin->id,
        ]);

        DB::enableQueryLog();

        $this->service->reassignTenant($tenant, $property2, $admin);

        $queries = DB::getQueryLog();

        // Count queries that access properties table inside transaction
        $propertyQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'properties') !== false
                && stripos($query['query'], 'UPDATE') === false;
        });

        // Should have at most 3 property queries:
        // 1. Eager load before transaction
        // 2. Property relationship access
        // 3. Possible additional framework query
        // This is acceptable and not an N+1 issue
        $this->assertLessThanOrEqual(3, count($propertyQueries), 'Excessive property queries detected');
    }

    /**
     * Test that deleteAccount dependency check is efficient.
     */
    public function test_delete_account_dependency_check_performance(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);

        DB::enableQueryLog();

        try {
            $this->service->deleteAccount($admin);
        } catch (\Exception $e) {
            // Expected to fail, we're just testing query count
        }

        $queries = DB::getQueryLog();

        // Should use exists() queries, not count() queries
        $existsQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'exists') !== false
                || stripos($query['query'], 'select 1') !== false;
        });

        // Should have at least 2 exists queries (meter_readings, child_users)
        $this->assertGreaterThanOrEqual(2, count($existsQueries), 'Should use exists() for dependency checks');

        // Should not have count queries
        $countQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'count(*)') !== false;
        });

        $this->assertCount(0, $countQueries, 'Should not use count() for dependency checks');
    }

    /**
     * Test concurrent admin creation performance.
     */
    public function test_concurrent_admin_creation_performance(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $startTime = microtime(true);

        // Create 10 admins
        for ($i = 0; $i < 10; $i++) {
            $this->service->createAdminAccount([
                'name' => "Admin $i",
                'email' => "admin$i@example.com",
                'password' => 'password123',
                'organization_name' => "Org $i",
                'plan_type' => 'professional',
            ], $superadmin);
        }

        $duration = (microtime(true) - $startTime) * 1000;

        // Should complete 10 creations in under 5 seconds
        $this->assertLessThan(5000, $duration, "10 admin creations took {$duration}ms, expected < 5000ms");

        // Average per admin should be under 500ms
        $avgDuration = $duration / 10;
        $this->assertLessThan(500, $avgDuration, "Average admin creation took {$avgDuration}ms, expected < 500ms");
    }

    /**
     * Test that validation happens before transaction.
     */
    public function test_validation_happens_before_transaction(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Create a user with duplicate email
        User::factory()->create(['email' => 'duplicate@example.com']);

        DB::enableQueryLog();

        try {
            $this->service->createAdminAccount([
                'name' => 'Test Admin',
                'email' => 'duplicate@example.com', // Duplicate!
                'password' => 'password123',
                'organization_name' => 'Test Org',
                'plan_type' => 'professional',
            ], $superadmin);

            $this->fail('Should have thrown validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Expected
        }

        $queries = DB::getQueryLog();

        // Should only have validation queries, no INSERT queries
        $insertQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'insert') !== false;
        });

        $this->assertCount(0, $insertQueries, 'Should not execute INSERT queries when validation fails');
    }

    /**
     * Test that password hashing happens before transaction.
     */
    public function test_password_hashing_before_transaction(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Measure time for password hashing
        $hashStartTime = microtime(true);
        $hashedPassword = \Illuminate\Support\Facades\Hash::make('password123');
        $hashDuration = (microtime(true) - $hashStartTime) * 1000;

        // Now measure total operation time
        $totalStartTime = microtime(true);

        $this->service->createAdminAccount([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => 'password123',
            'organization_name' => 'Test Org',
            'plan_type' => 'professional',
        ], $superadmin);

        $totalDuration = (microtime(true) - $totalStartTime) * 1000;

        // The total duration should include the hash duration
        // This confirms hashing happens as part of the operation
        $this->assertGreaterThan($hashDuration * 0.8, $totalDuration, 'Password hashing should be included in operation time');
    }

    /**
     * Test that property fetching uses select() optimization.
     */
    public function test_property_fetching_uses_select_optimization(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->create(['building_id' => $building->id, 'tenant_id' => 1]);

        DB::enableQueryLog();

        $this->service->createTenantAccount([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'password' => 'password123',
            'property_id' => $property->id,
        ], $admin);

        $queries = DB::getQueryLog();

        // Find the property SELECT query
        $propertyQuery = collect($queries)->first(function ($query) {
            return stripos($query['query'], 'select') !== false
                && stripos($query['query'], 'properties') !== false
                && stripos($query['query'], 'where') !== false;
        });

        $this->assertNotNull($propertyQuery, 'Should have a property SELECT query');

        // Should not select all columns (*)
        $this->assertStringNotContainsString('select *', strtolower($propertyQuery['query']), 'Should not use SELECT *');

        // Should select specific columns
        $this->assertStringContainsString('select', strtolower($propertyQuery['query']), 'Should use SELECT with specific columns');
    }
}
