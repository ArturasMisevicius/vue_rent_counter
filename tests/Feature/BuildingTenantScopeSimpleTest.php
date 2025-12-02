<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Building Tenant Scope Isolation - Simple Verification Tests
 * 
 * This test suite provides straightforward, easy-to-understand verification of tenant
 * scope isolation for the Building model. Unlike the comprehensive property-based tests
 * in FilamentBuildingResourceTenantScopeTest.php, these tests use fixed data scenarios
 * to demonstrate core tenant isolation principles.
 * 
 * Purpose:
 * - Verify basic tenant scope isolation at the model level
 * - Provide clear examples of expected tenant isolation behavior
 * - Serve as documentation for tenant scope implementation
 * - Quick smoke tests for tenant isolation functionality
 * 
 * Test Strategy:
 * - Uses fixed tenant IDs (1 and 2) for clarity
 * - Creates minimal test data (one building per tenant)
 * - Tests direct model queries (Building::all(), Building::find())
 * - Verifies both manager and superadmin access patterns
 * 
 * Related Tests:
 * - FilamentBuildingResourceTenantScopeTest.php - Comprehensive property-based tests
 * - MultiTenancyTest.php - Cross-model tenant isolation tests
 * 
 * Related Documentation:
 * - docs/testing/building-tenant-scope-simple-tests.md
 * - docs/architecture/multi-tenancy.md
 * - .kiro/specs/4-filament-admin-panel/tasks.md (Task 7.3)
 * 
 * @see \App\Models\Building
 * @see \App\Traits\BelongsToTenant
 * @see \App\Scopes\TenantScope
 */
/**
 * Test: Manager Tenant Isolation - Basic Verification
 * 
 * Verifies that a manager user can only access buildings belonging to their assigned
 * tenant. This test demonstrates the fundamental tenant isolation principle at the
 * model query level.
 * 
 * Test Scenario:
 * 1. Create two separate tenants (tenant_id: 1 and 2)
 * 2. Create one building for each tenant
 * 3. Authenticate as a manager assigned to tenant 1
 * 4. Verify manager can only see tenant 1's building
 * 5. Verify tenant 2's building is completely inaccessible
 * 
 * Expected Behavior:
 * - Building::all() returns only buildings from manager's tenant
 * - Building::find() returns null for cross-tenant building IDs
 * - TenantScope automatically filters queries by authenticated user's tenant_id
 * 
 * Tenant Scope Mechanism:
 * - BelongsToTenant trait applies TenantScope to Building model
 * - TenantScope adds WHERE tenant_id = ? clause to all queries
 * - Session stores authenticated user's tenant_id
 * - Scope is applied automatically via global scope registration
 * 
 * Security Implications:
 * - Prevents data leakage between tenants
 * - Enforces data isolation at the database query level
 * - No additional authorization checks needed in controllers
 * - Works consistently across all query methods (all, find, where, etc.)
 * 
 * @covers \App\Models\Building
 * @covers \App\Traits\BelongsToTenant
 * @covers \App\Scopes\TenantScope
 */
test('manager can only see their own tenant buildings', function (): void {
    // Create two tenants with buildings
    $tenant1 = 1;
    $tenant2 = 2;
    
    // Create buildings for tenant 1
    $building1 = Building::factory()->create([
        'tenant_id' => $tenant1,
        'name' => 'Tenant 1 Building',
    ]);
    
    // Create buildings for tenant 2
    $building2 = Building::factory()->create([
        'tenant_id' => $tenant2,
        'name' => 'Tenant 2 Building',
    ]);
    
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant1,
    ]);
    
    // Authenticate as manager 1
    $this->actingAs($manager1);
    
    // Manager 1 should only see their building
    $buildings = Building::all();
    
    expect($buildings)->toHaveCount(1, 'Manager should see exactly 1 building');
    expect($buildings->first()->id)->toBe($building1->id, 'Manager should see their own building');
    
    // Verify tenant 2's building is not accessible
    expect(Building::find($building2->id))->toBeNull('Tenant 2 building should be invisible to tenant 1 manager');
});

/**
 * Test: Superadmin Cross-Tenant Access - Unrestricted Visibility
 * 
 * Verifies that superadmin users have unrestricted access to buildings across all
 * tenants. This test demonstrates the superadmin bypass mechanism for tenant scope.
 * 
 * Test Scenario:
 * 1. Create two separate tenants (tenant_id: 1 and 2)
 * 2. Create one building for each tenant
 * 3. Authenticate as a superadmin (tenant_id: null)
 * 4. Verify superadmin can see buildings from both tenants
 * 5. Verify both building IDs are present in query results
 * 
 * Expected Behavior:
 * - Building::all() returns buildings from all tenants
 * - No tenant filtering is applied to queries
 * - TenantScope is bypassed when user has null tenant_id
 * - Superadmin can access any building by ID
 * 
 * Superadmin Bypass Mechanism:
 * - Superadmin users have tenant_id = null
 * - TenantScope checks for null tenant_id before applying filter
 * - When tenant_id is null, scope does not add WHERE clause
 * - This allows platform-wide administration and monitoring
 * 
 * Use Cases:
 * - Platform administration and monitoring
 * - Cross-tenant reporting and analytics
 * - System maintenance and troubleshooting
 * - Tenant onboarding and configuration
 * 
 * Security Considerations:
 * - Superadmin role should be strictly limited
 * - Audit logging should track all superadmin actions
 * - Consider additional authorization for sensitive operations
 * - Regular review of superadmin access is recommended
 * 
 * @covers \App\Models\Building
 * @covers \App\Scopes\TenantScope::apply
 * @covers \App\Enums\UserRole::SUPERADMIN
 */
test('superadmin can see all tenant buildings', function (): void {
    // Create two tenants with buildings
    $tenant1 = 1;
    $tenant2 = 2;
    
    // Create buildings for tenant 1
    $building1 = Building::factory()->create([
        'tenant_id' => $tenant1,
        'name' => 'Tenant 1 Building',
    ]);
    
    // Create buildings for tenant 2
    $building2 = Building::factory()->create([
        'tenant_id' => $tenant2,
        'name' => 'Tenant 2 Building',
    ]);
    
    // Create superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    // Authenticate as superadmin
    $this->actingAs($superadmin);
    
    // Superadmin should see all buildings
    $buildings = Building::all();
    
    expect($buildings)->toHaveCount(2, 'Superadmin should see all buildings');
    
    $buildingIds = $buildings->pluck('id')->toArray();
    
    // Verify both specific buildings are present
    expect(in_array($building1->id, $buildingIds))->toBeTrue('Should see tenant 1 building');
    expect(in_array($building2->id, $buildingIds))->toBeTrue('Should see tenant 2 building');
});

/**
 * Test: Direct ID Access Prevention - Cross-Tenant Security
 * 
 * Verifies that tenant scope isolation prevents managers from accessing buildings
 * from other tenants even when they know the building ID. This test demonstrates
 * that tenant scope protection works at the query level, not just the UI level.
 * 
 * Test Scenario:
 * 1. Create two separate tenants (tenant_id: 1 and 2)
 * 2. Create one building for each tenant
 * 3. Authenticate as a manager assigned to tenant 1
 * 4. Verify manager can access their own building by ID
 * 5. Verify manager cannot access tenant 2's building by ID
 * 
 * Expected Behavior:
 * - Building::find($ownBuildingId) returns the building
 * - Building::find($otherTenantBuildingId) returns null
 * - TenantScope applies to all query methods including find()
 * - No exception is thrown, query simply returns null
 * 
 * Security Implications:
 * - Prevents unauthorized access via direct ID manipulation
 * - Protects against URL parameter tampering
 * - Ensures API endpoints cannot be exploited with known IDs
 * - Works consistently across REST, GraphQL, and Livewire
 * 
 * Attack Scenarios Prevented:
 * - URL manipulation: /buildings/123 -> /buildings/456
 * - API parameter injection: ?building_id=456
 * - Form field tampering: <input value="456">
 * - Direct database ID guessing
 * 
 * Implementation Details:
 * - TenantScope adds WHERE tenant_id = ? to find() queries
 * - If building exists but belongs to different tenant, returns null
 * - Same behavior as if building doesn't exist at all
 * - Prevents information disclosure about other tenants' data
 * 
 * Related Security Tests:
 * - FilamentBuildingResourceTenantScopeTest::edit page isolation
 * - MultiTenancyTest::cross-tenant access prevention
 * - BuildingPolicy::view authorization checks
 * 
 * @covers \App\Models\Building::find
 * @covers \App\Scopes\TenantScope::apply
 * @covers \App\Traits\BelongsToTenant
 */
test('manager cannot access another tenant building by ID', function (): void {
    // Create two tenants with buildings
    $tenant1 = 1;
    $tenant2 = 2;
    
    // Create buildings for tenant 1
    $building1 = Building::factory()->create([
        'tenant_id' => $tenant1,
        'name' => 'Tenant 1 Building',
    ]);
    
    // Create buildings for tenant 2
    $building2 = Building::factory()->create([
        'tenant_id' => $tenant2,
        'name' => 'Tenant 2 Building',
    ]);
    
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant1,
    ]);
    
    // Authenticate as manager 1
    $this->actingAs($manager1);
    
    // Manager 1 can access their own building
    expect(Building::find($building1->id))
        ->not->toBeNull('Manager should access their own building')
        ->id->toBe($building1->id);
    
    // Manager 1 cannot access tenant 2's building
    expect(Building::find($building2->id))
        ->toBeNull('Manager should not access another tenant building');
});
