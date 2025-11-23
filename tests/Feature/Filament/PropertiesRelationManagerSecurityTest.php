<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * PropertiesRelationManager Security Tests
 * 
 * Tests security features including:
 * - Mass assignment protection
 * - Tenant scope isolation
 * - Audit logging
 * - Authorization checks
 * - PII masking in logs
 */

// ============================================================================
// MASS ASSIGNMENT PROTECTION TESTS
// ============================================================================

test('only whitelisted fields are saved when creating property', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    $address = fake()->address();
    
    // Attempt to create property with extra unauthorized fields
    $component
        ->callTableAction('create', data: [
            'address' => $address,
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
            // Unauthorized fields that should be ignored
            'is_premium' => true,
            'discount_rate' => 0.15,
            'custom_field' => 'malicious_value',
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify property was created with only whitelisted fields
    $property = Property::where('address', strip_tags(trim($address)))->first();
    expect($property)->not->toBeNull();
    expect($property->address)->toBe(strip_tags(trim($address)));
    expect($property->type->value)->toBe(PropertyType::APARTMENT->value);
    expect((float) $property->area_sqm)->toBe(50.0);
    
    // Verify unauthorized fields were not saved
    expect($property->toArray())->not->toHaveKey('is_premium');
    expect($property->toArray())->not->toHaveKey('discount_rate');
    expect($property->toArray())->not->toHaveKey('custom_field');
});

test('tenant_id cannot be overridden via form data', function () {
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId1]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    $address = fake()->address();
    
    // Attempt to create property with different tenant_id
    $component
        ->callTableAction('create', data: [
            'address' => $address,
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
            'tenant_id' => $tenantId2, // Attempt to override
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify property was created with correct tenant_id (from authenticated user)
    $property = Property::withoutGlobalScopes()
        ->where('address', strip_tags(trim($address)))
        ->first();
    
    expect($property)->not->toBeNull();
    expect($property->tenant_id)->toBe($tenantId1); // Should be manager's tenant_id
    expect($property->tenant_id)->not->toBe($tenantId2); // Should NOT be the attempted override
});

test('building_id cannot be overridden via form data', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building1, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    $address = fake()->address();
    
    // Attempt to create property with different building_id
    $component
        ->callTableAction('create', data: [
            'address' => $address,
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
            'building_id' => $building2->id, // Attempt to override
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify property was created with correct building_id (from owner record)
    $property = Property::where('address', strip_tags(trim($address)))->first();
    expect($property)->not->toBeNull();
    expect($property->building_id)->toBe($building1->id); // Should be owner record's building_id
    expect($property->building_id)->not->toBe($building2->id); // Should NOT be the attempted override
});

test('mass assignment attempts are logged with warning', function () {
    Log::spy();
    
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Attempt to create property with unauthorized fields
    $component
        ->callTableAction('create', data: [
            'address' => fake()->address(),
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
            'is_premium' => true,
            'discount_rate' => 0.15,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify warning was logged
    Log::shouldHaveReceived('warning')
        ->once()
        ->with(
            'Attempted mass assignment with unauthorized fields',
            \Mockery::on(function ($context) use ($manager) {
                return isset($context['extra_fields'])
                    && in_array('is_premium', $context['extra_fields'])
                    && in_array('discount_rate', $context['extra_fields'])
                    && $context['user_id'] === $manager->id;
            })
        );
});

// ============================================================================
// TENANT SCOPE ISOLATION TESTS
// ============================================================================

test('properties are automatically scoped to authenticated user tenant', function () {
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    // Create properties for both tenants
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'building_id' => $building1->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'building_id' => $building2->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building1, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Get table records
    $tableRecords = $component->instance()->getTableRecords();
    
    // Verify only tenant1's properties are visible
    expect($tableRecords->pluck('id')->toArray())->toContain($property1->id);
    expect($tableRecords->pluck('id')->toArray())->not->toContain($property2->id);
    
    // Verify all records belong to tenant1
    $tableRecords->each(function ($property) use ($tenantId1) {
        expect($property->tenant_id)->toBe($tenantId1);
    });
});

test('manager cannot edit property from different tenant', function () {
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'building_id' => $building2->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building1, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Attempt to edit property from different tenant
    try {
        $component->callTableAction('edit', $property2->id, data: [
            'address' => 'Updated Address',
            'type' => PropertyType::HOUSE->value,
            'area_sqm' => 100.0,
        ]);
        
        // Should not reach here
        expect(false)->toBeTrue('Manager should not be able to edit another tenant\'s property');
    } catch (\Exception $e) {
        // Expected - property should not be accessible
        expect(true)->toBeTrue();
    }
    
    // Verify property was not modified
    $property2->refresh();
    expect($property2->address)->not->toBe('Updated Address');
});

// ============================================================================
// AUDIT LOGGING TESTS
// ============================================================================

test('tenant assignment is logged with full audit trail', function () {
    Log::spy();
    
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
        'email' => 'manager@example.com',
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'name' => fake()->name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Assign tenant to property
    $component
        ->callTableAction('manage_tenant', $property->id, data: [
            'tenant_id' => $tenant->id,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify audit log was created
    Log::shouldHaveReceived('info')
        ->once()
        ->with(
            'Tenant management action',
            \Mockery::on(function ($context) use ($property, $tenant, $manager) {
                return $context['action'] === 'tenant_assigned'
                    && $context['property_id'] === $property->id
                    && $context['new_tenant_id'] === $tenant->id
                    && $context['user_id'] === $manager->id
                    && isset($context['timestamp']);
            })
        );
});

test('tenant removal is logged with full audit trail', function () {
    Log::spy();
    
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'name' => fake()->name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
    ]);
    
    // Assign tenant first
    $property->tenants()->attach($tenant->id);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Remove tenant from property
    $component
        ->callTableAction('manage_tenant', $property->id, data: [
            'tenant_id' => null,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify audit log was created
    Log::shouldHaveReceived('info')
        ->once()
        ->with(
            'Tenant management action',
            \Mockery::on(function ($context) use ($property, $tenant, $manager) {
                return $context['action'] === 'tenant_removed'
                    && $context['property_id'] === $property->id
                    && $context['previous_tenant_id'] === $tenant->id
                    && $context['new_tenant_id'] === null
                    && $context['user_id'] === $manager->id;
            })
        );
});

test('email addresses are masked in audit logs', function () {
    Log::spy();
    
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
        'email' => 'john.doe@example.com',
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'name' => fake()->name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Assign tenant
    $component
        ->callTableAction('manage_tenant', $property->id, data: [
            'tenant_id' => $tenant->id,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify email is masked in logs
    Log::shouldHaveReceived('info')
        ->once()
        ->with(
            'Tenant management action',
            \Mockery::on(function ($context) {
                // Email should be masked (e.g., jo***@example.com)
                return isset($context['user_email'])
                    && str_contains($context['user_email'], '***')
                    && str_contains($context['user_email'], '@');
            })
        );
});

test('IP addresses are masked in audit logs', function () {
    Log::spy();
    
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'name' => fake()->name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Assign tenant
    $component
        ->callTableAction('manage_tenant', $property->id, data: [
            'tenant_id' => $tenant->id,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify IP is masked in logs
    Log::shouldHaveReceived('info')
        ->once()
        ->with(
            'Tenant management action',
            \Mockery::on(function ($context) {
                // IP should be masked (e.g., 192.168.1.xxx)
                return isset($context['ip_address'])
                    && str_contains($context['ip_address'], 'xxx');
            })
        );
});

test('unauthorized access attempts are logged', function () {
    Log::spy();
    
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a tenant user (not manager) who shouldn't have update permission
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId1,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $tenantRecord = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'name' => fake()->name(),
        'email' => fake()->email(),
        'phone' => fake()->phoneNumber(),
    ]);
    
    $this->actingAs($tenant);
    session(['tenant_id' => $tenantId1]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Attempt to manage tenant (should fail authorization)
    $component
        ->callTableAction('manage_tenant', $property->id, data: [
            'tenant_id' => $tenantRecord->id,
        ]);
    
    // Verify unauthorized access was logged
    Log::shouldHaveReceived('warning')
        ->once()
        ->with(
            'Unauthorized tenant management attempt',
            \Mockery::on(function ($context) use ($property, $tenant) {
                return $context['property_id'] === $property->id
                    && $context['user_id'] === $tenant->id
                    && $context['user_role'] === UserRole::TENANT->value;
            })
        );
});
