<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant can only access data for their assigned property', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create multiple buildings and properties for this admin
    $building1 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $building2 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    
    $property1 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building1->id,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    $property3 = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Create meters for each property
    $meter1 = Meter::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property1->id,
    ]);
    
    $meter2 = Meter::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property2->id,
    ]);
    
    $meter3 = Meter::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property3->id,
    ]);
    
    // Create meter readings for each meter
    $reading1 = MeterReading::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'meter_id' => $meter1->id,
    ]);
    
    $reading2 = MeterReading::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'meter_id' => $meter2->id,
    ]);
    
    $reading3 = MeterReading::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'meter_id' => $meter3->id,
    ]);
    
    // Create invoices for each property
    $invoice1 = Invoice::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $invoice2 = Invoice::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $invoice3 = Invoice::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    // Create a tenant assigned to property1
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Verify tenant has correct tenant_id and property_id
    expect($tenant->tenant_id)->toBe($admin->tenant_id)
        ->and($tenant->property_id)->toBe($property1->id)
        ->and($tenant->role)->toBe(UserRole::TENANT);
    
    // Act as the tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see their assigned property
    $properties = Property::all();
    expect($properties)->toHaveCount(1)
        ->and($properties->first()->id)->toBe($property1->id)
        ->and($properties->pluck('id')->toArray())->not->toContain($property2->id)
        ->and($properties->pluck('id')->toArray())->not->toContain($property3->id);
    
    // Property: Tenant should only see meters for their assigned property
    $meters = Meter::all();
    expect($meters)->toHaveCount(1)
        ->and($meters->first()->id)->toBe($meter1->id)
        ->and($meters->first()->property_id)->toBe($property1->id)
        ->and($meters->pluck('id')->toArray())->not->toContain($meter2->id)
        ->and($meters->pluck('id')->toArray())->not->toContain($meter3->id);
    
    // Property: Tenant should only see meter readings for their assigned property
    $readings = MeterReading::all();
    expect($readings)->toHaveCount(1)
        ->and($readings->first()->id)->toBe($reading1->id)
        ->and($readings->first()->meter_id)->toBe($meter1->id)
        ->and($readings->pluck('id')->toArray())->not->toContain($reading2->id)
        ->and($readings->pluck('id')->toArray())->not->toContain($reading3->id);
    
    // Property: Tenant should only see invoices for their tenant_id (but not property-specific filtering for invoices)
    $invoices = Invoice::all();
    expect($invoices)->toHaveCount(3) // All invoices for the tenant_id
        ->and($invoices->pluck('id')->toArray())->toContain($invoice1->id)
        ->and($invoices->pluck('id')->toArray())->toContain($invoice2->id)
        ->and($invoices->pluck('id')->toArray())->toContain($invoice3->id);
    
    // Verify all returned invoices have the correct tenant_id
    foreach ($invoices as $invoice) {
        expect($invoice->tenant_id)->toBe($admin->tenant_id);
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant cannot access resources from different properties within same tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create multiple properties for this admin
    $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    
    $propertyCount = fake()->numberBetween(3, 5);
    $properties = [];
    $meters = [];
    $readings = [];
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        $properties[] = $property;
        
        $meter = Meter::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property->id,
        ]);
        $meters[] = $meter;
        
        $reading = MeterReading::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'meter_id' => $meter->id,
        ]);
        $readings[] = $reading;
    }
    
    // Create a tenant assigned to the first property
    $assignedProperty = $properties[0];
    $assignedMeter = $meters[0];
    $assignedReading = $readings[0];
    
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $assignedProperty->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Act as the tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see their assigned property
    $visibleProperties = Property::all();
    expect($visibleProperties)->toHaveCount(1)
        ->and($visibleProperties->first()->id)->toBe($assignedProperty->id);
    
    // Verify tenant cannot see other properties
    for ($i = 1; $i < $propertyCount; $i++) {
        expect($visibleProperties->pluck('id')->toArray())->not->toContain($properties[$i]->id);
    }
    
    // Property: Tenant should only see meters for their assigned property
    $visibleMeters = Meter::all();
    expect($visibleMeters)->toHaveCount(1)
        ->and($visibleMeters->first()->id)->toBe($assignedMeter->id)
        ->and($visibleMeters->first()->property_id)->toBe($assignedProperty->id);
    
    // Verify tenant cannot see other meters
    for ($i = 1; $i < $propertyCount; $i++) {
        expect($visibleMeters->pluck('id')->toArray())->not->toContain($meters[$i]->id);
    }
    
    // Property: Tenant should only see meter readings for their assigned property
    $visibleReadings = MeterReading::all();
    expect($visibleReadings)->toHaveCount(1)
        ->and($visibleReadings->first()->id)->toBe($assignedReading->id)
        ->and($visibleReadings->first()->meter_id)->toBe($assignedMeter->id);
    
    // Verify tenant cannot see other readings
    for ($i = 1; $i < $propertyCount; $i++) {
        expect($visibleReadings->pluck('id')->toArray())->not->toContain($readings[$i]->id);
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant cannot access resources from different tenant_ids', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create two admin accounts with different tenant_ids
    $admin1Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Verify admins have different tenant_ids
    expect($admin1->tenant_id)->not->toBe($admin2->tenant_id);
    
    // Create resources for admin1
    $building1 = Building::factory()->create(['tenant_id' => $admin1->tenant_id]);
    $property1 = Property::factory()->create([
        'tenant_id' => $admin1->tenant_id,
        'building_id' => $building1->id,
    ]);
    $meter1 = Meter::factory()->create([
        'tenant_id' => $admin1->tenant_id,
        'property_id' => $property1->id,
    ]);
    $reading1 = MeterReading::factory()->create([
        'tenant_id' => $admin1->tenant_id,
        'meter_id' => $meter1->id,
    ]);
    
    // Create resources for admin2
    $building2 = Building::factory()->create(['tenant_id' => $admin2->tenant_id]);
    $property2 = Property::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'building_id' => $building2->id,
    ]);
    $meter2 = Meter::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'property_id' => $property2->id,
    ]);
    $reading2 = MeterReading::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'meter_id' => $meter2->id,
    ]);
    
    // Create a tenant for admin1's property
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $property1->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin1);
    
    // Verify tenant belongs to admin1's tenant_id
    expect($tenant->tenant_id)->toBe($admin1->tenant_id)
        ->and($tenant->property_id)->toBe($property1->id);
    
    // Act as the tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see resources from their tenant_id
    $properties = Property::all();
    expect($properties)->toHaveCount(1)
        ->and($properties->first()->id)->toBe($property1->id)
        ->and($properties->first()->tenant_id)->toBe($admin1->tenant_id)
        ->and($properties->pluck('id')->toArray())->not->toContain($property2->id);
    
    $meters = Meter::all();
    expect($meters)->toHaveCount(1)
        ->and($meters->first()->id)->toBe($meter1->id)
        ->and($meters->first()->tenant_id)->toBe($admin1->tenant_id)
        ->and($meters->pluck('id')->toArray())->not->toContain($meter2->id);
    
    $readings = MeterReading::all();
    expect($readings)->toHaveCount(1)
        ->and($readings->first()->id)->toBe($reading1->id)
        ->and($readings->first()->tenant_id)->toBe($admin1->tenant_id)
        ->and($readings->pluck('id')->toArray())->not->toContain($reading2->id);
    
    // Property: Tenant should not see buildings (they don't have property_id filtering)
    $buildings = Building::all();
    expect($buildings)->toHaveCount(1)
        ->and($buildings->first()->id)->toBe($building1->id)
        ->and($buildings->first()->tenant_id)->toBe($admin1->tenant_id)
        ->and($buildings->pluck('id')->toArray())->not->toContain($building2->id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant property isolation works with complex queries and relationships', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create multiple buildings and properties
    $building1 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    $building2 = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    
    $tenantProperty = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building1->id,
    ]);
    
    $otherProperty = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Create meters for both properties
    $tenantMeter = Meter::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'property_id' => $tenantProperty->id,
    ]);
    
    $otherMeter = Meter::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'property_id' => $otherProperty->id,
    ]);
    
    // Create multiple readings for each meter
    $tenantReadingCount = fake()->numberBetween(2, 4);
    $otherReadingCount = fake()->numberBetween(2, 4);
    
    for ($i = 0; $i < $tenantReadingCount; $i++) {
        MeterReading::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'meter_id' => $tenantMeter->id,
        ]);
    }
    
    for ($i = 0; $i < $otherReadingCount; $i++) {
        MeterReading::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'meter_id' => $otherMeter->id,
        ]);
    }
    
    // Create a tenant assigned to the first property
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $tenantProperty->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Act as the tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see meters with their property relationship
    $metersWithProperty = Meter::with('property')->get();
    expect($metersWithProperty)->toHaveCount(1)
        ->and($metersWithProperty->first()->id)->toBe($tenantMeter->id)
        ->and($metersWithProperty->first()->property->id)->toBe($tenantProperty->id);
    
    // Property: Tenant should only see readings with their meter relationship
    $readingsWithMeter = MeterReading::with('meter')->get();
    expect($readingsWithMeter)->toHaveCount($tenantReadingCount);
    
    foreach ($readingsWithMeter as $reading) {
        expect($reading->meter->id)->toBe($tenantMeter->id)
            ->and($reading->meter->property_id)->toBe($tenantProperty->id);
    }
    
    // Property: Tenant should only see their property when querying with where clauses
    $propertiesInBuilding1 = Property::where('building_id', $building1->id)->get();
    expect($propertiesInBuilding1)->toHaveCount(1)
        ->and($propertiesInBuilding1->first()->id)->toBe($tenantProperty->id);
    
    $propertiesInBuilding2 = Property::where('building_id', $building2->id)->get();
    expect($propertiesInBuilding2)->toHaveCount(0); // Should not see the other property
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant can find their assigned property by id but not others', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create an admin account
    $adminData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => 'basic',
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin = $accountService->createAdminAccount($adminData, $superadmin);
    
    // Create multiple properties
    $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
    
    $tenantProperty = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    $otherProperty = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);
    
    // Create a tenant assigned to the first property
    $tenantData = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'property_id' => $tenantProperty->id,
    ];
    
    $tenant = $accountService->createTenantAccount($tenantData, $admin);
    
    // Act as the tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should be able to find their assigned property by ID
    $foundProperty = Property::find($tenantProperty->id);
    expect($foundProperty)->not->toBeNull()
        ->and($foundProperty->id)->toBe($tenantProperty->id);
    
    // Property: Tenant should NOT be able to find other properties by ID
    $notFoundProperty = Property::find($otherProperty->id);
    expect($notFoundProperty)->toBeNull();
})->repeat(100);