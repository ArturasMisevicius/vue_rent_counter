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

// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from accessing admin-only resources', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users attempting to access admin-only resources should be denied
    expect(UserResource::canViewAny())->toBeFalse(
        'Tenant users should be denied access to UserResource'
    );
    
    expect(TariffResource::canViewAny())->toBeFalse(
        'Tenant users should be denied access to TariffResource'
    );
    
    expect(ProviderResource::canViewAny())->toBeFalse(
        'Tenant users should be denied access to ProviderResource'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from accessing operational resources', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users attempting to access operational resources should be denied
    expect(PropertyResource::canViewAny())->toBeFalse(
        'Tenant users should be denied access to PropertyResource'
    );
    
    expect(MeterResource::canViewAny())->toBeFalse(
        'Tenant users should be denied access to MeterResource'
    );
    
    expect(BuildingResource::canViewAny())->toBeFalse(
        'Tenant users should be denied access to BuildingResource'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies manager users from accessing admin-only resources', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users attempting to access admin-only resources should be denied
    expect(UserResource::canViewAny())->toBeFalse(
        'Manager users should be denied access to UserResource'
    );
    
    expect(TariffResource::canViewAny())->toBeFalse(
        'Manager users should be denied access to TariffResource'
    );
    
    expect(ProviderResource::canViewAny())->toBeFalse(
        'Manager users should be denied access to ProviderResource'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from creating resources', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users attempting to create resources should be denied
    expect(InvoiceResource::canCreate())->toBeFalse(
        'Tenant users should be denied from creating invoices'
    );
    
    expect(MeterReadingResource::canCreate())->toBeFalse(
        'Tenant users should be denied from creating meter readings'
    );
    
    expect(PropertyResource::canCreate())->toBeFalse(
        'Tenant users should be denied from creating properties'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies users from viewing resources outside their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a property for tenant 2
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a manager for tenant 1
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be denied from viewing resources outside their tenant scope
    $canView = $manager->can('view', $property);
    expect($canView)->toBeFalse(
        'Manager should be denied from viewing properties outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies users from editing resources outside their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a building for tenant 2
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    // Create a manager for tenant 1
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be denied from editing resources outside their tenant scope
    $canEdit = $manager->can('update', $building);
    expect($canEdit)->toBeFalse(
        'Manager should be denied from editing buildings outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies users from deleting resources outside their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a meter for tenant 2
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create a manager for tenant 1
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be denied from deleting resources outside their tenant scope
    $canDelete = $manager->can('delete', $meter);
    expect($canDelete)->toBeFalse(
        'Manager should be denied from deleting meters outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from editing their own invoices', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a property for the tenant
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    
    // Associate the tenant with the property
    $property->tenants()->attach($tenant);
    
    // Create an invoice for the tenant
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users should be denied from editing invoices (even their own)
    $canEdit = $tenantUser->can('update', $invoice);
    expect($canEdit)->toBeFalse(
        'Tenant users should be denied from editing invoices'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from deleting their own invoices', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a property for the tenant
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    
    // Associate the tenant with the property
    $property->tenants()->attach($tenant);
    
    // Create an invoice for the tenant
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users should be denied from deleting invoices (even their own)
    $canDelete = $tenantUser->can('delete', $invoice);
    expect($canDelete)->toBeFalse(
        'Tenant users should be denied from deleting invoices'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from editing meter readings', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a property for the tenant
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    
    // Associate the tenant with the property
    $property->tenants()->attach($tenant);
    
    // Create a meter for the property
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a meter reading
    $meterReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users should be denied from editing meter readings
    $canEdit = $tenantUser->can('update', $meterReading);
    expect($canEdit)->toBeFalse(
        'Tenant users should be denied from editing meter readings'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies tenant users from deleting meter readings', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a property for the tenant
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    
    // Associate the tenant with the property
    $property->tenants()->attach($tenant);
    
    // Create a meter for the property
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a meter reading
    $meterReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users should be denied from deleting meter readings
    $canDelete = $tenantUser->can('delete', $meterReading);
    expect($canDelete)->toBeFalse(
        'Tenant users should be denied from deleting meter readings'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies users from viewing invoices outside their tenant scope', function () {
    $tenant1 = Tenant::factory()->forTenantId(fake()->numberBetween(1, 1000))->create();
    $tenant2 = Tenant::factory()->forTenantId(fake()->numberBetween(1001, 2000))->create();

    // Create an invoice for tenant 2
    $invoice = Invoice::factory()
        ->forTenantRenter($tenant2)
        ->create([
            'tenant_id' => $tenant2->tenant_id,
        ]);
    
    // Create a manager for tenant 1
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant1->tenant_id,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager);
    session(['tenant_id' => $tenant1->tenant_id]);
    
    // Property: Manager should be denied from viewing invoices outside their tenant scope
    $canView = $manager->can('view', $invoice);
    expect($canView)->toBeFalse(
        'Manager should be denied from viewing invoices outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies users from viewing meter readings outside their tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a meter reading for tenant 2
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    $reading = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    // Create a manager for tenant 1
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be denied from viewing meter readings outside their tenant scope
    $canView = $manager->can('view', $reading);
    expect($canView)->toBeFalse(
        'Manager should be denied from viewing meter readings outside their tenant scope'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies manager users from managing system-wide resources', function () {
    // Create system-wide resources
    $provider = Provider::factory()->create([
        'name' => fake()->company(),
    ]);
    
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);
    
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users should be denied from managing system-wide resources
    $canViewProvider = $managerUser->can('view', $provider);
    expect($canViewProvider)->toBeFalse(
        'Manager users should be denied from viewing providers'
    );
    
    $canUpdateProvider = $managerUser->can('update', $provider);
    expect($canUpdateProvider)->toBeFalse(
        'Manager users should be denied from updating providers'
    );
    
    $canDeleteProvider = $managerUser->can('delete', $provider);
    expect($canDeleteProvider)->toBeFalse(
        'Manager users should be denied from deleting providers'
    );
    
    $canViewTariff = $managerUser->can('view', $tariff);
    expect($canViewTariff)->toBeFalse(
        'Manager users should be denied from viewing tariffs'
    );
    
    $canUpdateTariff = $managerUser->can('update', $tariff);
    expect($canUpdateTariff)->toBeFalse(
        'Manager users should be denied from updating tariffs'
    );
    
    $canDeleteTariff = $managerUser->can('delete', $tariff);
    expect($canDeleteTariff)->toBeFalse(
        'Manager users should be denied from deleting tariffs'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 23: Authorization denial for restricted resources
// Validates: Requirements 9.4
test('Filament panel denies manager users from managing user accounts', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create users with different roles
    $anotherManager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId2,
    ]);
    
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId2,
    ]);
    
    $adminUser = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a manager user
    $managerUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as the manager user
    $this->actingAs($managerUser);
    
    // Property: Manager users should be denied from managing user accounts
    $canViewManager = $managerUser->can('view', $anotherManager);
    expect($canViewManager)->toBeFalse(
        'Manager users should be denied from viewing other manager users'
    );
    
    $canUpdateManager = $managerUser->can('update', $anotherManager);
    expect($canUpdateManager)->toBeFalse(
        'Manager users should be denied from updating other manager users'
    );
    
    $canViewTenant = $managerUser->can('view', $tenantUser);
    expect($canViewTenant)->toBeFalse(
        'Manager users should be denied from viewing tenant users'
    );
    
    $canUpdateTenant = $managerUser->can('update', $tenantUser);
    expect($canUpdateTenant)->toBeFalse(
        'Manager users should be denied from updating tenant users'
    );
    
    $canViewAdmin = $managerUser->can('view', $adminUser);
    expect($canViewAdmin)->toBeFalse(
        'Manager users should be denied from viewing admin users'
    );
    
    $canUpdateAdmin = $managerUser->can('update', $adminUser);
    expect($canUpdateAdmin)->toBeFalse(
        'Manager users should be denied from updating admin users'
    );
})->repeat(100);
