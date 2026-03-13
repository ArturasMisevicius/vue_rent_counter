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
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel hides operational resources from tenant users in navigation', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users should not see operational resources in navigation
    // Resources that should be HIDDEN from tenant users:
    // - PropertyResource (managers only)
    // - MeterResource (managers only)
    // - UserResource (admins only)
    // - TariffResource (admins only)
    // - ProviderResource (admins only)
    // - BuildingResource (managers and admins)
    
    expect(PropertyResource::shouldRegisterNavigation())->toBeFalse(
        'PropertyResource should be hidden from tenant users'
    );
    
    expect(MeterResource::shouldRegisterNavigation())->toBeFalse(
        'MeterResource should be hidden from tenant users'
    );
    
    expect(UserResource::shouldRegisterNavigation())->toBeFalse(
        'UserResource should be hidden from tenant users'
    );
    
    expect(TariffResource::shouldRegisterNavigation())->toBeFalse(
        'TariffResource should be hidden from tenant users'
    );
    
    expect(ProviderResource::shouldRegisterNavigation())->toBeFalse(
        'ProviderResource should be hidden from tenant users'
    );
    
    expect(BuildingResource::shouldRegisterNavigation())->toBeFalse(
        'BuildingResource should be hidden from tenant users'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel shows tenant-specific resources to tenant users in navigation', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users SHOULD see tenant-specific resources in navigation
    // Resources that should be VISIBLE to tenant users:
    // - InvoiceResource (can view their own invoices)
    // - MeterReadingResource (can view their own meter readings)
    
    expect(InvoiceResource::shouldRegisterNavigation())->toBeTrue(
        'InvoiceResource should be visible to tenant users'
    );
    
    expect(MeterReadingResource::shouldRegisterNavigation())->toBeTrue(
        'MeterReadingResource should be visible to tenant users'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from viewing PropertyResource list page', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot access PropertyResource
    expect(PropertyResource::canViewAny())->toBeFalse(
        'Tenant users should not be able to view PropertyResource'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from creating properties', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot create properties
    expect(PropertyResource::canCreate())->toBeFalse(
        'Tenant users should not be able to create properties'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from viewing MeterResource list page', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot access MeterResource
    expect(MeterResource::canViewAny())->toBeFalse(
        'Tenant users should not be able to view MeterResource'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from creating meter readings', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot create meter readings (only view)
    expect(MeterReadingResource::canCreate())->toBeFalse(
        'Tenant users should not be able to create meter readings'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel allows tenant users to view their own invoices', function () {
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
    
    // Property: Tenant users CAN view their own invoices
    expect(InvoiceResource::canViewAny())->toBeTrue(
        'Tenant users should be able to view invoices'
    );
    
    // Verify they can view their specific invoice
    $canView = $tenantUser->can('view', $invoice);
    expect($canView)->toBeTrue(
        'Tenant users should be able to view their own invoices'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from creating invoices', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot create invoices
    expect(InvoiceResource::canCreate())->toBeFalse(
        'Tenant users should not be able to create invoices'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel allows tenant users to view meter readings for their properties', function () {
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
    
    // Property: Tenant users CAN view meter readings for their properties
    expect(MeterReadingResource::canViewAny())->toBeTrue(
        'Tenant users should be able to view meter readings'
    );
    
    // Verify they can view their specific meter reading
    $canView = $tenantUser->can('view', $meterReading);
    expect($canView)->toBeTrue(
        'Tenant users should be able to view meter readings for their properties'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from accessing UserResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot access UserResource
    expect(UserResource::canViewAny())->toBeFalse(
        'Tenant users should not be able to access UserResource'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from accessing TariffResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot access TariffResource
    expect(TariffResource::canViewAny())->toBeFalse(
        'Tenant users should not be able to access TariffResource'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from accessing ProviderResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot access ProviderResource
    expect(ProviderResource::canViewAny())->toBeFalse(
        'Tenant users should not be able to access ProviderResource'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from accessing BuildingResource', function () {
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create a tenant user
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the tenant user
    $this->actingAs($tenantUser);
    
    // Property: Tenant users cannot access BuildingResource
    expect(BuildingResource::canViewAny())->toBeFalse(
        'Tenant users should not be able to access BuildingResource'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from editing or deleting invoices', function () {
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
    
    // Property: Tenant users cannot edit or delete invoices
    $canEdit = $tenantUser->can('update', $invoice);
    expect($canEdit)->toBeFalse(
        'Tenant users should not be able to edit invoices'
    );
    
    $canDelete = $tenantUser->can('delete', $invoice);
    expect($canDelete)->toBeFalse(
        'Tenant users should not be able to delete invoices'
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 20: Tenant role resource restriction
// Validates: Requirements 9.1
test('Filament panel denies tenant users from editing or deleting meter readings', function () {
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
    
    // Property: Tenant users cannot edit or delete meter readings
    $canEdit = $tenantUser->can('update', $meterReading);
    expect($canEdit)->toBeFalse(
        'Tenant users should not be able to edit meter readings'
    );
    
    $canDelete = $tenantUser->can('delete', $meterReading);
    expect($canDelete)->toBeFalse(
        'Tenant users should not be able to delete meter readings'
    );
})->repeat(100);
