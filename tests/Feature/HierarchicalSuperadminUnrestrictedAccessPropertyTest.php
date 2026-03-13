<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionPlanType;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
// Validates: Requirements 1.4, 12.2, 13.1
test('superadmin can access all resources across all tenant_ids', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admin accounts with different tenant_ids
    $adminCount = fake()->numberBetween(2, 5);
    $allBuildings = [];
    $allProperties = [];
    $allMeters = [];
    $allMeterReadings = [];
    $allInvoices = [];
    $allTenants = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        // Create an admin account
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => fake()->randomElement(SubscriptionPlanType::values()),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 365))->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Create resources for this admin
        $buildingCount = fake()->numberBetween(1, 3);
        for ($j = 0; $j < $buildingCount; $j++) {
            $building = Building::factory()->create([
                'tenant_id' => $admin->tenant_id,
            ]);
            $allBuildings[] = $building->id;
            
            $property = Property::factory()->create([
                'tenant_id' => $admin->tenant_id,
                'building_id' => $building->id,
            ]);
            $allProperties[] = $property->id;
            
            $meter = Meter::factory()->create([
                'tenant_id' => $admin->tenant_id,
                'property_id' => $property->id,
            ]);
            $allMeters[] = $meter->id;
            
            $meterReading = MeterReading::factory()->create([
                'tenant_id' => $admin->tenant_id,
                'meter_id' => $meter->id,
            ]);
            $allMeterReadings[] = $meterReading->id;
            
            $invoice = Invoice::factory()->create([
                'tenant_id' => $admin->tenant_id,
            ]);
            $allInvoices[] = $invoice->id;
            
            // Create a tenant for this property
            $tenantData = [
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password123',
                'name' => fake()->name(),
                'property_id' => $property->id,
            ];
            
            $tenant = $accountService->createTenantAccount($tenantData, $admin);
            $allTenants[] = $tenant->id;
        }
    }
    
    // Act as superadmin and query all resources
    $this->actingAs($superadmin);
    
    // Property: Superadmin should see ALL buildings across all tenant_ids
    $buildings = Building::all();
    expect($buildings->pluck('id')->toArray())->toHaveCount(count($allBuildings))
        ->and($buildings->pluck('id')->sort()->values()->toArray())
        ->toBe(collect($allBuildings)->sort()->values()->toArray());
    
    // Property: Superadmin should see ALL properties across all tenant_ids
    $properties = Property::all();
    expect($properties->pluck('id')->toArray())->toHaveCount(count($allProperties))
        ->and($properties->pluck('id')->sort()->values()->toArray())
        ->toBe(collect($allProperties)->sort()->values()->toArray());
    
    // Property: Superadmin should see ALL meters across all tenant_ids
    $meters = Meter::all();
    expect($meters->pluck('id')->toArray())->toHaveCount(count($allMeters))
        ->and($meters->pluck('id')->sort()->values()->toArray())
        ->toBe(collect($allMeters)->sort()->values()->toArray());
    
    // Property: Superadmin should see ALL meter readings across all tenant_ids
    $meterReadings = MeterReading::all();
    expect($meterReadings->pluck('id')->toArray())->toHaveCount(count($allMeterReadings))
        ->and($meterReadings->pluck('id')->sort()->values()->toArray())
        ->toBe(collect($allMeterReadings)->sort()->values()->toArray());
    
    // Property: Superadmin should see ALL invoices across all tenant_ids
    $invoices = Invoice::all();
    expect($invoices->pluck('id')->toArray())->toHaveCount(count($allInvoices))
        ->and($invoices->pluck('id')->sort()->values()->toArray())
        ->toBe(collect($allInvoices)->sort()->values()->toArray());
})->repeat(100);

// Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
// Validates: Requirements 1.4, 12.2, 13.1
test('superadmin bypasses tenant_id filtering on all models', function () {
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
        'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
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
    
    // Create resources for admin2
    $building2 = Building::factory()->create(['tenant_id' => $admin2->tenant_id]);
    $property2 = Property::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'building_id' => $building2->id,
    ]);
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Property: Superadmin should see resources from both tenant_ids
    $buildings = Building::all();
    expect($buildings)->toHaveCount(2)
        ->and($buildings->pluck('id')->toArray())->toContain($building1->id)
        ->and($buildings->pluck('id')->toArray())->toContain($building2->id);
    
    $properties = Property::all();
    expect($properties)->toHaveCount(2)
        ->and($properties->pluck('id')->toArray())->toContain($property1->id)
        ->and($properties->pluck('id')->toArray())->toContain($property2->id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
// Validates: Requirements 1.4, 12.2, 13.1
test('superadmin can query specific resources by id across tenant boundaries', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admins with resources
    $adminCount = fake()->numberBetween(2, 4);
    $resourceIds = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        
        $resourceIds[] = [
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
            'property_id' => $property->id,
        ];
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Property: Superadmin should be able to find any resource by ID regardless of tenant_id
    foreach ($resourceIds as $ids) {
        $building = Building::find($ids['building_id']);
        expect($building)->not->toBeNull()
            ->and($building->id)->toBe($ids['building_id'])
            ->and($building->tenant_id)->toBe($ids['tenant_id']);
        
        $property = Property::find($ids['property_id']);
        expect($property)->not->toBeNull()
            ->and($property->id)->toBe($ids['property_id'])
            ->and($property->tenant_id)->toBe($ids['tenant_id']);
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
// Validates: Requirements 1.4, 12.2, 13.1
test('superadmin sees all resources while admin sees only their tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create two admins
    $admin1Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Create resources for both admins
    $building1 = Building::factory()->create(['tenant_id' => $admin1->tenant_id]);
    $building2 = Building::factory()->create(['tenant_id' => $admin2->tenant_id]);
    
    // Act as superadmin - should see both buildings
    $this->actingAs($superadmin);
    $superadminBuildings = Building::all();
    expect($superadminBuildings)->toHaveCount(2)
        ->and($superadminBuildings->pluck('id')->toArray())->toContain($building1->id)
        ->and($superadminBuildings->pluck('id')->toArray())->toContain($building2->id);
    
    // Act as admin1 - should see only their building
    $this->actingAs($admin1);
    $admin1Buildings = Building::all();
    expect($admin1Buildings)->toHaveCount(1)
        ->and($admin1Buildings->first()->id)->toBe($building1->id)
        ->and($admin1Buildings->pluck('id')->toArray())->not->toContain($building2->id);
    
    // Act as admin2 - should see only their building
    $this->actingAs($admin2);
    $admin2Buildings = Building::all();
    expect($admin2Buildings)->toHaveCount(1)
        ->and($admin2Buildings->first()->id)->toBe($building2->id)
        ->and($admin2Buildings->pluck('id')->toArray())->not->toContain($building1->id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
// Validates: Requirements 1.4, 12.2, 13.1
test('superadmin can access resources with where clauses across all tenants', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admins with buildings
    $adminCount = fake()->numberBetween(2, 4);
    $allBuildingIds = [];
    $specificName = 'Test Building ' . fake()->unique()->word();
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Create a building with a specific name for this admin
        $building = Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => $specificName,
        ]);
        $allBuildingIds[] = $building->id;
        
        // Create another building with a different name
        Building::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Other Building ' . fake()->word(),
        ]);
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Property: Superadmin should find all buildings with the specific name across all tenant_ids
    $buildings = Building::where('name', $specificName)->get();
    expect($buildings)->toHaveCount($adminCount)
        ->and($buildings->pluck('id')->sort()->values()->toArray())
        ->toBe(collect($allBuildingIds)->sort()->values()->toArray());
})->repeat(100);

// Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
// Validates: Requirements 1.4, 12.2, 13.1
test('superadmin unrestricted access works with complex queries', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admins with properties and meters
    $adminCount = fake()->numberBetween(2, 3);
    $totalMeterCount = 0;
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => AppnumsSubscriptionPlanType::BASIC->value,
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        
        $propertyCount = fake()->numberBetween(1, 3);
        for ($j = 0; $j < $propertyCount; $j++) {
            $property = Property::factory()->create([
                'tenant_id' => $admin->tenant_id,
                'building_id' => $building->id,
            ]);
            
            $meterCount = fake()->numberBetween(1, 2);
            for ($k = 0; $k < $meterCount; $k++) {
                Meter::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'property_id' => $property->id,
                ]);
                $totalMeterCount++;
            }
        }
    }
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Property: Superadmin should see all meters with joins across all tenant_ids
    $metersWithProperties = Meter::with('property')->get();
    expect($metersWithProperties)->toHaveCount($totalMeterCount);
    
    // Verify each meter has a property loaded
    foreach ($metersWithProperties as $meter) {
        expect($meter->property)->not->toBeNull();
    }
})->repeat(100);
