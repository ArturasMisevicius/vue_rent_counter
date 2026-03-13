<?php

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MeterReadingResource;
use App\Filament\Resources\MeterResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\ProviderResource;
use App\Filament\Resources\TariffResource;
use App\Filament\Resources\UserResource;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel shows operational resources to manager users in navigation', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users SHOULD see operational resources in navigation
    // Resources that should be VISIBLE to manager users:
    // - PropertyResource
    // - MeterResource
    // - MeterReadingResource
    // - InvoiceResource
    // - BuildingResource
    
    expect(PropertyResource::shouldRegisterNavigation())->toBeTrue(
        'PropertyResource should be visible to manager users'
    );
    
    expect(MeterResource::shouldRegisterNavigation())->toBeTrue(
        'MeterResource should be visible to manager users'
    );
    
    expect(MeterReadingResource::shouldRegisterNavigation())->toBeTrue(
        'MeterReadingResource should be visible to manager users'
    );
    
    expect(InvoiceResource::shouldRegisterNavigation())->toBeTrue(
        'InvoiceResource should be visible to manager users'
    );
    
    expect(BuildingResource::shouldRegisterNavigation())->toBeTrue(
        'BuildingResource should be visible to manager users'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel hides admin-only resources from manager users in navigation', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users should NOT see admin-only resources in navigation
    // Resources that should be HIDDEN from manager users:
    // - UserResource (admins only)
    // - TariffResource (admins only)
    // - ProviderResource (admins only)
    
    expect(UserResource::shouldRegisterNavigation())->toBeFalse(
        'UserResource should be hidden from manager users'
    );
    
    expect(TariffResource::shouldRegisterNavigation())->toBeFalse(
        'TariffResource should be hidden from manager users'
    );
    
    expect(ProviderResource::shouldRegisterNavigation())->toBeFalse(
        'ProviderResource should be hidden from manager users'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to access PropertyResource with tenant scope', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users CAN access PropertyResource
    expect(PropertyResource::canViewAny())->toBeTrue(
        'Manager users should be able to access PropertyResource'
    );
    
    expect(PropertyResource::canCreate())->toBeTrue(
        'Manager users should be able to create properties'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to access MeterResource with tenant scope', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users CAN access MeterResource
    expect(MeterResource::canViewAny())->toBeTrue(
        'Manager users should be able to access MeterResource'
    );
    
    expect(MeterResource::canCreate())->toBeTrue(
        'Manager users should be able to create meters'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to access MeterReadingResource with tenant scope', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users CAN access MeterReadingResource
    expect(MeterReadingResource::canViewAny())->toBeTrue(
        'Manager users should be able to access MeterReadingResource'
    );
    
    expect(MeterReadingResource::canCreate())->toBeTrue(
        'Manager users should be able to create meter readings'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to access InvoiceResource with tenant scope', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users CAN access InvoiceResource
    expect(InvoiceResource::canViewAny())->toBeTrue(
        'Manager users should be able to access InvoiceResource'
    );
    
    expect(InvoiceResource::canCreate())->toBeTrue(
        'Manager users should be able to create invoices'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to access BuildingResource with tenant scope', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users CAN access BuildingResource
    expect(BuildingResource::canViewAny())->toBeTrue(
        'Manager users should be able to access BuildingResource'
    );
    
    expect(BuildingResource::canCreate())->toBeTrue(
        'Manager users should be able to create buildings'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel denies manager users from accessing UserResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users cannot access UserResource (admin only)
    expect(UserResource::canViewAny())->toBeFalse(
        'Manager users should not be able to access UserResource'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel denies manager users from accessing TariffResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users cannot access TariffResource (admin only)
    expect(TariffResource::canViewAny())->toBeFalse(
        'Manager users should not be able to access TariffResource'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel denies manager users from accessing ProviderResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users cannot access ProviderResource (admin only)
    expect(ProviderResource::canViewAny())->toBeFalse(
        'Manager users should not be able to access ProviderResource'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel restricts manager users to only view resources within their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create properties for both tenants
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to view their own tenant's property
    $canViewOwn = $manager1->can('view', $property1);
    expect($canViewOwn)->toBeTrue(
        'Manager should be able to view properties within their tenant scope'
    );
    
    // Property: Manager should NOT be able to view another tenant's property
    $canViewOther = $manager1->can('view', $property2);
    expect($canViewOther)->toBeFalse(
        'Manager should not be able to view properties outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel restricts manager users to only edit resources within their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create buildings for both tenants
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
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to edit their own tenant's building
    $canEditOwn = $manager1->can('update', $building1);
    expect($canEditOwn)->toBeTrue(
        'Manager should be able to edit buildings within their tenant scope'
    );
    
    // Property: Manager should NOT be able to edit another tenant's building
    $canEditOther = $manager1->can('update', $building2);
    expect($canEditOther)->toBeFalse(
        'Manager should not be able to edit buildings outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel restricts manager users to only delete resources within their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create meters for both tenants
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to delete their own tenant's meter
    $canDeleteOwn = $manager1->can('delete', $meter1);
    expect($canDeleteOwn)->toBeTrue(
        'Manager should be able to delete meters within their tenant scope'
    );
    
    // Property: Manager should NOT be able to delete another tenant's meter
    $canDeleteOther = $manager1->can('delete', $meter2);
    expect($canDeleteOther)->toBeFalse(
        'Manager should not be able to delete meters outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to view invoices only within their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create invoices for both tenants
    $invoice1 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'invoice_number' => fake()->unique()->numerify('INV-####'),
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => 'draft',
    ]);
    
    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'invoice_number' => fake()->unique()->numerify('INV-####'),
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => 'draft',
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to view their own tenant's invoice
    $canViewOwn = $manager1->can('view', $invoice1);
    expect($canViewOwn)->toBeTrue(
        'Manager should be able to view invoices within their tenant scope'
    );
    
    // Property: Manager should NOT be able to view another tenant's invoice
    $canViewOther = $manager1->can('view', $invoice2);
    expect($canViewOther)->toBeFalse(
        'Manager should not be able to view invoices outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 21: Manager role resource access with tenant scope
// Validates: Requirements 9.2
test('Filament panel allows manager users to view meter readings only within their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create meter readings for both tenants
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $reading1 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'meter_id' => $meter1->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $reading2 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'meter_id' => $meter2->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to view their own tenant's meter reading
    $canViewOwn = $manager1->can('view', $reading1);
    expect($canViewOwn)->toBeTrue(
        'Manager should be able to view meter readings within their tenant scope'
    );
    
    // Property: Manager should NOT be able to view another tenant's meter reading
    $canViewOther = $manager1->can('view', $reading2);
    expect($canViewOther)->toBeFalse(
        'Manager should not be able to view meter readings outside their tenant scope'
    );
})->repeat(100);
