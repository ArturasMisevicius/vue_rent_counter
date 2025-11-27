<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Helper function to create buildings for a specific tenant.
 *
 * @param int $tenantId The tenant ID to assign to buildings
 * @param int $count Number of buildings to create
 * @return Collection<int, Building> Collection of created buildings
 */
function createBuildingsForTenant(int $tenantId, int $count): Collection
{
    return Building::factory()
        ->count($count)
        ->create(['tenant_id' => $tenantId]);
}

/**
 * Helper function to create a manager user for a specific tenant.
 *
 * @param int $tenantId The tenant ID to assign to the manager
 * @return User The created manager user
 */
function createManagerForTenant(int $tenantId): User
{
    return User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
}

/**
 * Helper function to create a superadmin user.
 *
 * @return User The created superadmin user
 */
function createSuperadmin(): User
{
    return User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
}

/**
 * Helper function to authenticate as a user and set session tenant.
 *
 * @param User $user The user to authenticate as
 * @return void
 */
function authenticateWithTenant(User $user): void
{
    test()->actingAs($user);
    session(['tenant_id' => $user->tenant_id]);
}

/**
 * Feature: filament-admin-panel, Property 16: Tenant scope isolation for buildings
 * Validates: Requirements 7.1
 *
 * This property-based test verifies that BuildingResource correctly filters buildings
 * by the authenticated user's tenant_id, ensuring complete tenant isolation.
 *
 * Test Strategy:
 * - Creates random number of buildings for two different tenants
 * - Authenticates as manager from tenant 1, verifies only tenant 1's buildings visible
 * - Switches to manager from tenant 2, verifies only tenant 2's buildings visible
 * - Confirms cross-tenant buildings are completely inaccessible
 * - Runs 100 iterations with randomized data for statistical confidence
 */
test('BuildingResource automatically filters buildings by authenticated user tenant_id', function (): void {
    // Generate random tenant IDs to avoid conflicts
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of buildings for each tenant (2-8 buildings)
    $buildingsCount1 = fake()->numberBetween(2, 8);
    $buildingsCount2 = fake()->numberBetween(2, 8);
    
    // Create buildings using factory for better maintainability
    $buildings1 = createBuildingsForTenant($tenantId1, $buildingsCount1);
    $buildings2 = createBuildingsForTenant($tenantId2, $buildingsCount2);
    
    // Test Tenant 1 Manager Access
    $manager1 = createManagerForTenant($tenantId1);
    authenticateWithTenant($manager1);
    
    // Property: When accessing BuildingResource list page, only tenant 1's buildings should be visible
    $component = Livewire::test(BuildingResource\Pages\ListBuildings::class);
    $component->assertSuccessful();
    
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: All returned buildings should belong to tenant 1
    expect($tableRecords)
        ->toHaveCount($buildingsCount1, 'Manager should see exactly their tenant\'s buildings')
        ->each(fn ($building) => $building->tenant_id->toBe($tenantId1, 'All buildings must belong to tenant 1'));
    
    // Property: Tenant 2's buildings should not be accessible via direct query
    $buildings2->each(fn ($building) => 
        expect(Building::find($building->id))
            ->toBeNull('Tenant 2 buildings should be invisible to tenant 1 manager')
    );
    
    // Verify all tenant 1's buildings are present in the table
    expect($tableRecords->pluck('id')->toArray())
        ->toEqualCanonicalizing(
            $buildings1->pluck('id')->toArray(),
            'All tenant 1 buildings should be present in the table'
        );
    
    // Test Tenant 2 Manager Access
    $manager2 = createManagerForTenant($tenantId2);
    authenticateWithTenant($manager2);
    
    // Property: When accessing BuildingResource list page, only tenant 2's buildings should be visible
    $component2 = Livewire::test(BuildingResource\Pages\ListBuildings::class);
    $component2->assertSuccessful();
    
    $tableRecords2 = $component2->instance()->getTableRecords();
    
    // Property: All returned buildings should belong to tenant 2
    expect($tableRecords2)
        ->toHaveCount($buildingsCount2, 'Manager should see exactly their tenant\'s buildings')
        ->each(fn ($building) => $building->tenant_id->toBe($tenantId2, 'All buildings must belong to tenant 2'));
    
    // Property: Tenant 1's buildings should not be accessible via direct query
    $buildings1->each(fn ($building) => 
        expect(Building::find($building->id))
            ->toBeNull('Tenant 1 buildings should be invisible to tenant 2 manager')
    );
    
    // Verify all tenant 2's buildings are present in the table
    expect($tableRecords2->pluck('id')->toArray())
        ->toEqualCanonicalizing(
            $buildings2->pluck('id')->toArray(),
            'All tenant 2 buildings should be present in the table'
        );
})->repeat(100);

/**
 * Feature: filament-admin-panel, Property 16: Tenant scope isolation for buildings
 * Validates: Requirements 7.1
 *
 * This property-based test verifies that BuildingResource edit page enforces
 * tenant scope isolation, preventing managers from editing buildings outside
 * their tenant scope.
 *
 * Test Strategy:
 * - Creates one building for tenant 1 and one for tenant 2
 * - Authenticates as manager from tenant 1
 * - Verifies manager can access edit page for their tenant's building
 * - Verifies manager cannot access edit page for another tenant's building
 * - Expects ModelNotFoundException when attempting cross-tenant access
 * - Runs 100 iterations with randomized data for statistical confidence
 */
test('BuildingResource edit page only allows editing buildings within tenant scope', function (): void {
    // Generate random tenant IDs to avoid conflicts
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create one building for each tenant
    $building1 = createBuildingsForTenant($tenantId1, 1)->first();
    $building2 = createBuildingsForTenant($tenantId2, 1)->first();
    
    // Authenticate as manager from tenant 1
    $manager1 = createManagerForTenant($tenantId1);
    authenticateWithTenant($manager1);
    
    // Property: Manager should be able to access edit page for their tenant's building
    $component = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building1->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the correct building is loaded with proper tenant scope
    expect($component->instance()->record)
        ->id->toBe($building1->id, 'Correct building should be loaded')
        ->tenant_id->toBe($tenantId1, 'Building should belong to manager\'s tenant');
    
    // Property: Manager should NOT be able to access edit page for another tenant's building
    // This should throw ModelNotFoundException due to tenant scope
    expect(fn () => Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building2->id,
    ]))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
})->repeat(100);

/**
 * Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
 * Validates: Requirements 1.4, 12.2, 13.1
 *
 * This property-based test verifies that superadmin users have unrestricted
 * access to buildings across all tenants, bypassing tenant scope restrictions.
 *
 * Test Strategy:
 * - Creates one building for tenant 1 and one for tenant 2
 * - Authenticates as superadmin (tenant_id = null)
 * - Verifies superadmin can see buildings from all tenants in list view
 * - Verifies superadmin can edit buildings from any tenant
 * - Confirms cross-tenant access is fully enabled for superadmin role
 * - Runs 100 iterations with randomized data for statistical confidence
 */
test('Superadmin users can access buildings from all tenants', function (): void {
    // Generate random tenant IDs to avoid conflicts
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create one building for each tenant
    $building1 = createBuildingsForTenant($tenantId1, 1)->first();
    $building2 = createBuildingsForTenant($tenantId2, 1)->first();
    
    // Authenticate as superadmin (tenant_id = null for unrestricted access)
    $superadmin = createSuperadmin();
    authenticateWithTenant($superadmin);
    
    // Property: Superadmin should be able to see buildings from all tenants
    $component = Livewire::test(BuildingResource\Pages\ListBuildings::class);
    $component->assertSuccessful();
    
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: Superadmin should see at least the two buildings we created
    expect($tableRecords->count())
        ->toBeGreaterThanOrEqual(2, 'Superadmin should see buildings from all tenants');
    
    // Verify both buildings are accessible in the list
    $tableRecordIds = $tableRecords->pluck('id')->toArray();
    expect($tableRecordIds)
        ->toContain($building1->id, 'Tenant 1 building should be visible to superadmin')
        ->toContain($building2->id, 'Tenant 2 building should be visible to superadmin');
    
    // Property: Superadmin should be able to edit buildings from any tenant
    $component1 = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building1->id,
    ]);
    
    $component1->assertSuccessful();
    expect($component1->instance()->record->id)
        ->toBe($building1->id, 'Superadmin should access tenant 1 building edit page');
    
    $component2 = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building2->id,
    ]);
    
    $component2->assertSuccessful();
    expect($component2->instance()->record->id)
        ->toBe($building2->id, 'Superadmin should access tenant 2 building edit page');
})->repeat(100);
