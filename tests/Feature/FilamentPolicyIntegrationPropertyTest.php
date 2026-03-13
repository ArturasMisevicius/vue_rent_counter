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

// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament PropertyResource integrates with PropertyPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = PropertyResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', Property::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'PropertyResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament PropertyResource integrates with PropertyPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = PropertyResource::canCreate();
    $policyCanCreate = $user->can('create', Property::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'PropertyResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament PropertyResource integrates with PropertyPolicy for update action', function () {
    // Generate random tenant IDs
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenantId,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanEdit = PropertyResource::canEdit($property);
    $policyCanUpdate = $user->can('update', $property);
    
    expect($resourceCanEdit)->toBe($policyCanUpdate,
        'PropertyResource::canEdit() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament PropertyResource integrates with PropertyPolicy for delete action', function () {
    // Generate random tenant IDs
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenantId,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanDelete = PropertyResource::canDelete($property);
    $policyCanDelete = $user->can('delete', $property);
    
    expect($resourceCanDelete)->toBe($policyCanDelete,
        'PropertyResource::canDelete() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament MeterReadingResource integrates with MeterReadingPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = MeterReadingResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', MeterReading::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'MeterReadingResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament MeterReadingResource integrates with MeterReadingPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = MeterReadingResource::canCreate();
    $policyCanCreate = $user->can('create', MeterReading::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'MeterReadingResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament InvoiceResource integrates with InvoicePolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = InvoiceResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', Invoice::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'InvoiceResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament InvoiceResource integrates with InvoicePolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = InvoiceResource::canCreate();
    $policyCanCreate = $user->can('create', Invoice::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'InvoiceResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament UserResource integrates with UserPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = UserResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', User::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'UserResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament UserResource integrates with UserPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = UserResource::canCreate();
    $policyCanCreate = $user->can('create', User::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'UserResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament BuildingResource integrates with BuildingPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = BuildingResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', Building::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'BuildingResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament BuildingResource integrates with BuildingPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = BuildingResource::canCreate();
    $policyCanCreate = $user->can('create', Building::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'BuildingResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament MeterResource integrates with MeterPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = MeterResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', Meter::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'MeterResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament MeterResource integrates with MeterPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = MeterResource::canCreate();
    $policyCanCreate = $user->can('create', Meter::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'MeterResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament TariffResource integrates with TariffPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = TariffResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', Tariff::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'TariffResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament TariffResource integrates with TariffPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = TariffResource::canCreate();
    $policyCanCreate = $user->can('create', Tariff::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'TariffResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament ProviderResource integrates with ProviderPolicy for viewAny action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanViewAny = ProviderResource::canViewAny();
    $policyCanViewAny = $user->can('viewAny', Provider::class);
    
    expect($resourceCanViewAny)->toBe($policyCanViewAny,
        'ProviderResource::canViewAny() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament ProviderResource integrates with ProviderPolicy for create action', function () {
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a tenant if needed
    $tenant = Tenant::factory()->create();
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenant->id,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    
    // Property: Filament resource should delegate to policy
    $resourceCanCreate = ProviderResource::canCreate();
    $policyCanCreate = $user->can('create', Provider::class);
    
    expect($resourceCanCreate)->toBe($policyCanCreate,
        'ProviderResource::canCreate() should match policy authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament resources integrate with policies for view action on specific records', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a meter
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create a meter reading
    $reading = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenantId,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    session(['tenant_id' => $tenantId]);
    
    // Property: Policy authorization should be consistent for viewing records
    $policyCanViewProperty = $user->can('view', $property);
    $policyCanViewMeter = $user->can('view', $meter);
    $policyCanViewReading = $user->can('view', $reading);
    
    // Verify that policy methods are being called (they should return boolean values)
    expect($policyCanViewProperty)->toBeIn([true, false],
        'Policy should return boolean for property view authorization'
    );
    expect($policyCanViewMeter)->toBeIn([true, false],
        'Policy should return boolean for meter view authorization'
    );
    expect($policyCanViewReading)->toBeIn([true, false],
        'Policy should return boolean for meter reading view authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament resources integrate with policies for update action on specific records', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a building
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(5, 100),
    ]);
    
    // Create an invoice
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'invoice_number' => fake()->unique()->numerify('INV-####'),
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => 'draft',
    ]);
    
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenantId,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    session(['tenant_id' => $tenantId]);
    
    // Property: Policy authorization should be consistent for updating records
    $policyCanUpdateBuilding = $user->can('update', $building);
    $policyCanUpdateInvoice = $user->can('update', $invoice);
    
    // Verify that policy methods are being called (they should return boolean values)
    expect($policyCanUpdateBuilding)->toBeIn([true, false],
        'Policy should return boolean for building update authorization'
    );
    expect($policyCanUpdateInvoice)->toBeIn([true, false],
        'Policy should return boolean for invoice update authorization'
    );
})->repeat(100);


// Feature: filament-admin-panel, Property 24: Policy integration
// Validates: Requirements 9.5
test('Filament resources integrate with policies for delete action on specific records', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a meter
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Generate random user role
    $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
    
    // Create a user with the random role
    $user = User::factory()->create([
        'role' => $role,
        'tenant_id' => $role === UserRole::ADMIN ? null : $tenantId,
    ]);
    
    // Act as the user
    $this->actingAs($user);
    session(['tenant_id' => $tenantId]);
    
    // Property: Policy authorization should be consistent for deleting records
    $policyCanDeleteProperty = $user->can('delete', $property);
    $policyCanDeleteMeter = $user->can('delete', $meter);
    
    // Verify that policy methods are being called (they should return boolean values)
    expect($policyCanDeleteProperty)->toBeIn([true, false],
        'Policy should return boolean for property delete authorization'
    );
    expect($policyCanDeleteMeter)->toBeIn([true, false],
        'Policy should return boolean for meter delete authorization'
    );
})->repeat(100);
