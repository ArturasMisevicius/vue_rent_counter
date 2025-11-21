<?php

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 16: Tenant scope isolation for buildings
// Validates: Requirements 7.1
test('BuildingResource automatically filters buildings by authenticated user tenant_id', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of buildings for each tenant
    $buildingsCount1 = fake()->numberBetween(2, 8);
    $buildingsCount2 = fake()->numberBetween(2, 8);
    
    // Create buildings for tenant 1 without global scopes
    $buildings1 = [];
    for ($i = 0; $i < $buildingsCount1; $i++) {
        $buildings1[] = Building::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'address' => fake()->unique()->address(),
            'total_apartments' => fake()->numberBetween(5, 100),
        ]);
    }
    
    // Create buildings for tenant 2 without global scopes
    $buildings2 = [];
    for ($i = 0; $i < $buildingsCount2; $i++) {
        $buildings2[] = Building::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'address' => fake()->unique()->address(),
            'total_apartments' => fake()->numberBetween(5, 100),
        ]);
    }
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    
    // Set session tenant_id (this is what TenantScope uses)
    session(['tenant_id' => $tenantId1]);
    
    // Property: When accessing BuildingResource list page, only tenant 1's buildings should be visible
    $component = Livewire::test(BuildingResource\Pages\ListBuildings::class);
    
    // Verify the component loaded successfully
    $component->assertSuccessful();
    
    // Get the table records from the component
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: All returned buildings should belong to tenant 1
    expect($tableRecords)->toHaveCount($buildingsCount1);
    
    $tableRecords->each(function ($building) use ($tenantId1) {
        expect($building->tenant_id)->toBe($tenantId1);
    });
    
    // Property: Tenant 2's buildings should not be accessible
    foreach ($buildings2 as $building2) {
        expect(Building::find($building2->id))->toBeNull();
    }
    
    // Verify tenant 1's buildings are all present in the table
    $buildingIds1 = collect($buildings1)->pluck('id')->toArray();
    $tableRecordIds = $tableRecords->pluck('id')->toArray();
    
    expect($tableRecordIds)->toEqualCanonicalizing($buildingIds1);
    
    // Now switch to manager from tenant 2
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId2,
    ]);
    
    $this->actingAs($manager2);
    session(['tenant_id' => $tenantId2]);
    
    // Property: When accessing BuildingResource list page, only tenant 2's buildings should be visible
    $component2 = Livewire::test(BuildingResource\Pages\ListBuildings::class);
    
    $component2->assertSuccessful();
    
    $tableRecords2 = $component2->instance()->getTableRecords();
    
    // Property: All returned buildings should belong to tenant 2
    expect($tableRecords2)->toHaveCount($buildingsCount2);
    
    $tableRecords2->each(function ($building) use ($tenantId2) {
        expect($building->tenant_id)->toBe($tenantId2);
    });
    
    // Property: Tenant 1's buildings should not be accessible
    foreach ($buildings1 as $building1) {
        expect(Building::find($building1->id))->toBeNull();
    }
    
    // Verify tenant 2's buildings are all present in the table
    $buildingIds2 = collect($buildings2)->pluck('id')->toArray();
    $tableRecordIds2 = $tableRecords2->pluck('id')->toArray();
    
    expect($tableRecordIds2)->toEqualCanonicalizing($buildingIds2);
})->repeat(100);

// Feature: filament-admin-panel, Property 16: Tenant scope isolation for buildings
// Validates: Requirements 7.1
test('BuildingResource edit page only allows editing buildings within tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a building for tenant 1
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    // Create a building for tenant 2
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to access edit page for their tenant's building
    $component = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building1->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the correct building is loaded
    expect($component->instance()->record->id)->toBe($building1->id);
    expect($component->instance()->record->tenant_id)->toBe($tenantId1);
    
    // Property: Manager should NOT be able to access edit page for another tenant's building
    // This should fail because the building won't be found due to tenant scope
    try {
        $component2 = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
            'record' => $building2->id,
        ]);
        
        // If we get here, the test should fail because access should be denied
        expect(false)->toBeTrue('Manager should not be able to access another tenant\'s building');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This is expected - the building should not be found due to tenant scope
        expect(true)->toBeTrue();
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 16: Tenant scope isolation for buildings
// Validates: Requirements 7.1
test('BuildingResource create page automatically assigns tenant_id from authenticated user', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random building data
    $address = fake()->address();
    $totalApartments = fake()->numberBetween(5, 100);
    
    // Property: When creating a building through Filament, tenant_id should be automatically assigned
    $component = Livewire::test(BuildingResource\Pages\CreateBuilding::class);
    
    $component->assertSuccessful();
    
    // Fill the form and submit
    $component
        ->fillForm([
            'address' => $address,
            'total_apartments' => $totalApartments,
        ])
        ->call('create');
    
    // Verify the building was created with the correct tenant_id
    $createdBuilding = Building::withoutGlobalScopes()
        ->where('address', $address)
        ->first();
    
    expect($createdBuilding)->not->toBeNull();
    expect($createdBuilding->tenant_id)->toBe($tenantId);
    expect($createdBuilding->address)->toBe($address);
    expect($createdBuilding->total_apartments)->toBe($totalApartments);
})->repeat(100);

// Feature: filament-admin-panel, Property 16: Tenant scope isolation for buildings
// Validates: Requirements 7.1
test('Admin users can access buildings from all tenants', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create buildings for different tenants
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    // Create an admin user (admins have null tenant_id)
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin
    $this->actingAs($admin);
    session(['tenant_id' => null]);
    
    // Property: Admin should be able to see buildings from all tenants
    $component = Livewire::test(BuildingResource\Pages\ListBuildings::class);
    
    $component->assertSuccessful();
    
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: Admin should see at least the two buildings we created
    expect($tableRecords->count())->toBeGreaterThanOrEqual(2);
    
    // Verify both buildings are accessible
    $tableRecordIds = $tableRecords->pluck('id')->toArray();
    expect($tableRecordIds)->toContain($building1->id);
    expect($tableRecordIds)->toContain($building2->id);
    
    // Property: Admin should be able to edit buildings from any tenant
    $component1 = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building1->id,
    ]);
    
    $component1->assertSuccessful();
    expect($component1->instance()->record->id)->toBe($building1->id);
    
    $component2 = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building2->id,
    ]);
    
    $component2->assertSuccessful();
    expect($component2->instance()->record->id)->toBe($building2->id);
})->repeat(100);
