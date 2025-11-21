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
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel shows all resources to admin users in navigation', function () {
    // Create an admin user (no tenant_id required)
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin user
    $this->actingAs($adminUser);
    
    // Property: Admin users SHOULD see all resources in navigation
    // All resources should be VISIBLE to admin users:
    // - PropertyResource
    // - MeterResource
    // - MeterReadingResource
    // - InvoiceResource
    // - BuildingResource
    // - UserResource
    // - TariffResource
    // - ProviderResource
    
    expect(PropertyResource::shouldRegisterNavigation())->toBeTrue(
        'PropertyResource should be visible to admin users'
    );
    
    expect(MeterResource::shouldRegisterNavigation())->toBeTrue(
        'MeterResource should be visible to admin users'
    );
    
    expect(MeterReadingResource::shouldRegisterNavigation())->toBeTrue(
        'MeterReadingResource should be visible to admin users'
    );
    
    expect(InvoiceResource::shouldRegisterNavigation())->toBeTrue(
        'InvoiceResource should be visible to admin users'
    );
    
    expect(BuildingResource::shouldRegisterNavigation())->toBeTrue(
        'BuildingResource should be visible to admin users'
    );
    
    expect(UserResource::shouldRegisterNavigation())->toBeTrue(
        'UserResource should be visible to admin users'
    );
    
    expect(TariffResource::shouldRegisterNavigation())->toBeTrue(
        'TariffResource should be visible to admin users'
    );
    
    expect(ProviderResource::shouldRegisterNavigation())->toBeTrue(
        'ProviderResource should be visible to admin users'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to access all operational resources', function () {
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin user
    $this->actingAs($adminUser);
    
    // Property: Admin users CAN access all operational resources
    expect(PropertyResource::canViewAny())->toBeTrue(
        'Admin users should be able to access PropertyResource'
    );
    
    expect(PropertyResource::canCreate())->toBeTrue(
        'Admin users should be able to create properties'
    );
    
    expect(MeterResource::canViewAny())->toBeTrue(
        'Admin users should be able to access MeterResource'
    );
    
    expect(MeterResource::canCreate())->toBeTrue(
        'Admin users should be able to create meters'
    );
    
    expect(MeterReadingResource::canViewAny())->toBeTrue(
        'Admin users should be able to access MeterReadingResource'
    );
    
    expect(MeterReadingResource::canCreate())->toBeTrue(
        'Admin users should be able to create meter readings'
    );
    
    expect(InvoiceResource::canViewAny())->toBeTrue(
        'Admin users should be able to access InvoiceResource'
    );
    
    expect(InvoiceResource::canCreate())->toBeTrue(
        'Admin users should be able to create invoices'
    );
    
    expect(BuildingResource::canViewAny())->toBeTrue(
        'Admin users should be able to access BuildingResource'
    );
    
    expect(BuildingResource::canCreate())->toBeTrue(
        'Admin users should be able to create buildings'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to access all system configuration resources', function () {
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin user
    $this->actingAs($adminUser);
    
    // Property: Admin users CAN access all system configuration resources
    expect(UserResource::canViewAny())->toBeTrue(
        'Admin users should be able to access UserResource'
    );
    
    expect(UserResource::canCreate())->toBeTrue(
        'Admin users should be able to create users'
    );
    
    expect(TariffResource::canViewAny())->toBeTrue(
        'Admin users should be able to access TariffResource'
    );
    
    expect(TariffResource::canCreate())->toBeTrue(
        'Admin users should be able to create tariffs'
    );
    
    expect(ProviderResource::canViewAny())->toBeTrue(
        'Admin users should be able to access ProviderResource'
    );
    
    expect(ProviderResource::canCreate())->toBeTrue(
        'Admin users should be able to create providers'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to view resources across all tenants', function () {
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
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to view properties from any tenant
    $canViewTenant1 = $adminUser->can('view', $property1);
    expect($canViewTenant1)->toBeTrue(
        'Admin should be able to view properties from tenant 1'
    );
    
    $canViewTenant2 = $adminUser->can('view', $property2);
    expect($canViewTenant2)->toBeTrue(
        'Admin should be able to view properties from tenant 2'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to edit resources across all tenants', function () {
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
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to edit buildings from any tenant
    $canEditTenant1 = $adminUser->can('update', $building1);
    expect($canEditTenant1)->toBeTrue(
        'Admin should be able to edit buildings from tenant 1'
    );
    
    $canEditTenant2 = $adminUser->can('update', $building2);
    expect($canEditTenant2)->toBeTrue(
        'Admin should be able to edit buildings from tenant 2'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to delete resources across all tenants', function () {
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
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to delete meters from any tenant
    $canDeleteTenant1 = $adminUser->can('delete', $meter1);
    expect($canDeleteTenant1)->toBeTrue(
        'Admin should be able to delete meters from tenant 1'
    );
    
    $canDeleteTenant2 = $adminUser->can('delete', $meter2);
    expect($canDeleteTenant2)->toBeTrue(
        'Admin should be able to delete meters from tenant 2'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to view invoices across all tenants', function () {
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
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to view invoices from any tenant
    $canViewTenant1 = $adminUser->can('view', $invoice1);
    expect($canViewTenant1)->toBeTrue(
        'Admin should be able to view invoices from tenant 1'
    );
    
    $canViewTenant2 = $adminUser->can('view', $invoice2);
    expect($canViewTenant2)->toBeTrue(
        'Admin should be able to view invoices from tenant 2'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to view meter readings across all tenants', function () {
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
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to view meter readings from any tenant
    $canViewTenant1 = $adminUser->can('view', $reading1);
    expect($canViewTenant1)->toBeTrue(
        'Admin should be able to view meter readings from tenant 1'
    );
    
    $canViewTenant2 = $adminUser->can('view', $reading2);
    expect($canViewTenant2)->toBeTrue(
        'Admin should be able to view meter readings from tenant 2'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to manage system-wide resources without tenant scope', function () {
    // Create providers and tariffs (system-wide resources)
    $provider = Provider::factory()->create([
        'name' => fake()->company(),
    ]);
    
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to manage system-wide resources
    $canViewProvider = $adminUser->can('view', $provider);
    expect($canViewProvider)->toBeTrue(
        'Admin should be able to view providers'
    );
    
    $canUpdateProvider = $adminUser->can('update', $provider);
    expect($canUpdateProvider)->toBeTrue(
        'Admin should be able to update providers'
    );
    
    $canDeleteProvider = $adminUser->can('delete', $provider);
    expect($canDeleteProvider)->toBeTrue(
        'Admin should be able to delete providers'
    );
    
    $canViewTariff = $adminUser->can('view', $tariff);
    expect($canViewTariff)->toBeTrue(
        'Admin should be able to view tariffs'
    );
    
    $canUpdateTariff = $adminUser->can('update', $tariff);
    expect($canUpdateTariff)->toBeTrue(
        'Admin should be able to update tariffs'
    );
    
    $canDeleteTariff = $adminUser->can('delete', $tariff);
    expect($canDeleteTariff)->toBeTrue(
        'Admin should be able to delete tariffs'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to manage all user accounts', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create users with different roles and tenants
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId2,
    ]);
    
    $anotherAdminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create an admin user
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to manage all user accounts
    $canViewManager = $adminUser->can('view', $managerUser);
    expect($canViewManager)->toBeTrue(
        'Admin should be able to view manager users'
    );
    
    $canUpdateManager = $adminUser->can('update', $managerUser);
    expect($canUpdateManager)->toBeTrue(
        'Admin should be able to update manager users'
    );
    
    $canViewTenant = $adminUser->can('view', $tenantUser);
    expect($canViewTenant)->toBeTrue(
        'Admin should be able to view tenant users'
    );
    
    $canUpdateTenant = $adminUser->can('update', $tenantUser);
    expect($canUpdateTenant)->toBeTrue(
        'Admin should be able to update tenant users'
    );
    
    $canViewAdmin = $adminUser->can('view', $anotherAdminUser);
    expect($canViewAdmin)->toBeTrue(
        'Admin should be able to view other admin users'
    );
    
    $canUpdateAdmin = $adminUser->can('update', $anotherAdminUser);
    expect($canUpdateAdmin)->toBeTrue(
        'Admin should be able to update other admin users'
    );
})->repeat(100);
