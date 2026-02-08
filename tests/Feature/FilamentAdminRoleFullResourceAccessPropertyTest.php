<?php

declare(strict_types=1);

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

/*
|--------------------------------------------------------------------------
| Admin Role Full Resource Access Tests
|--------------------------------------------------------------------------
|
| These tests verify that ADMIN users can access all Filament resources
| within their tenant scope. Per the multi-tenancy design:
| - SUPERADMIN = Global access (no tenant_id, can access all tenants)
| - ADMIN = Tenant-scoped access (must have tenant_id, accesses own tenant)
| - MANAGER = Same as Admin (legacy role)
| - TENANT = Property-scoped access (can only see assigned property)
|
| Feature: filament-admin-panel, Property 22: Admin role full resource access
| Validates: Requirements 9.3
|
*/

// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel shows all resources to admin users in navigation', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create an admin user with tenant_id (admin must belong to a tenant)
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
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
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the admin user
    $this->actingAs($adminUser);
    
    // Property: Admin users CAN access all operational resources within their tenant
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
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
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
test('Filament panel allows admin users to view resources within their tenant', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create properties within the admin's tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to view properties within their tenant
    $canViewProperty1 = $adminUser->can('view', $property1);
    expect($canViewProperty1)->toBeTrue(
        'Admin should be able to view properties within their tenant'
    );
    
    $canViewProperty2 = $adminUser->can('view', $property2);
    expect($canViewProperty2)->toBeTrue(
        'Admin should be able to view all properties within their tenant'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to edit resources within their tenant', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create buildings within the admin's tenant
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to edit buildings within their tenant
    $canEditBuilding1 = $adminUser->can('update', $building1);
    expect($canEditBuilding1)->toBeTrue(
        'Admin should be able to edit buildings within their tenant'
    );
    
    $canEditBuilding2 = $adminUser->can('update', $building2);
    expect($canEditBuilding2)->toBeTrue(
        'Admin should be able to edit all buildings within their tenant'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to delete resources within their tenant', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create meters within the admin's tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property2->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to delete meters within their tenant
    $canDeleteMeter1 = $adminUser->can('delete', $meter1);
    expect($canDeleteMeter1)->toBeTrue(
        'Admin should be able to delete meters within their tenant'
    );
    
    $canDeleteMeter2 = $adminUser->can('delete', $meter2);
    expect($canDeleteMeter2)->toBeTrue(
        'Admin should be able to delete all meters within their tenant'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to view invoices within their tenant', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create renter records within the admin's tenant
    $tenant1 = Tenant::factory()->forTenantId($tenantId)->create();
    $tenant2 = Tenant::factory()->forTenantId($tenantId)->create();
    
    // Create invoices within the admin's tenant
    $invoice1 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenant1->id,
        'invoice_number' => fake()->unique()->numerify('INV-####'),
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => 'draft',
    ]);
    
    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenant2->id,
        'invoice_number' => fake()->unique()->numerify('INV-####'),
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => 'draft',
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to view invoices within their tenant
    $canViewInvoice1 = $adminUser->can('view', $invoice1);
    expect($canViewInvoice1)->toBeTrue(
        'Admin should be able to view invoices within their tenant'
    );
    
    $canViewInvoice2 = $adminUser->can('view', $invoice2);
    expect($canViewInvoice2)->toBeTrue(
        'Admin should be able to view all invoices within their tenant'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to view meter readings within their tenant', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create meter readings within the admin's tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $reading1 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter1->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property2->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $reading2 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter2->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to view meter readings within their tenant
    $canViewReading1 = $adminUser->can('view', $reading1);
    expect($canViewReading1)->toBeTrue(
        'Admin should be able to view meter readings within their tenant'
    );
    
    $canViewReading2 = $adminUser->can('view', $reading2);
    expect($canViewReading2)->toBeTrue(
        'Admin should be able to view all meter readings within their tenant'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 22: Admin role full resource access
// Validates: Requirements 9.3
test('Filament panel allows admin users to manage system-wide resources', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create providers and tariffs (system-wide resources)
    $provider = Provider::factory()->create([
        'name' => fake()->company(),
    ]);
    
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
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
test('Filament panel allows admin users to manage all user accounts within their tenant', function () {
    // Create a tenant ID for the admin user
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create users with different roles within the same tenant
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
    ]);
    
    $anotherAdminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Create an admin user with tenant_id
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as admin user
    $this->actingAs($adminUser);
    
    // Property: Admin should be able to manage all user accounts within their tenant
    $canViewManager = $adminUser->can('view', $managerUser);
    expect($canViewManager)->toBeTrue(
        'Admin should be able to view manager users within their tenant'
    );
    
    $canUpdateManager = $adminUser->can('update', $managerUser);
    expect($canUpdateManager)->toBeTrue(
        'Admin should be able to update manager users within their tenant'
    );
    
    $canViewTenant = $adminUser->can('view', $tenantUser);
    expect($canViewTenant)->toBeTrue(
        'Admin should be able to view tenant users within their tenant'
    );
    
    $canUpdateTenant = $adminUser->can('update', $tenantUser);
    expect($canUpdateTenant)->toBeTrue(
        'Admin should be able to update tenant users within their tenant'
    );
    
    $canViewAdmin = $adminUser->can('view', $anotherAdminUser);
    expect($canViewAdmin)->toBeTrue(
        'Admin should be able to view other admin users within their tenant'
    );
    
    $canUpdateAdmin = $adminUser->can('update', $anotherAdminUser);
    expect($canUpdateAdmin)->toBeTrue(
        'Admin should be able to update other admin users within their tenant'
    );
})->repeat(100);
