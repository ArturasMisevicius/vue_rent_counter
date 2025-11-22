<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 6: Resource creation inherits tenant_id
// Validates: Requirements 4.1, 4.4, 13.2
test('admin created buildings automatically inherit admin tenant_id', function () {
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
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Create multiple buildings
    $buildingCount = fake()->numberBetween(2, 5);
    $createdBuildings = [];
    
    for ($i = 0; $i < $buildingCount; $i++) {
        $building = Building::factory()->create([
            'name' => fake()->company() . ' Building',
            'address' => fake()->streetAddress() . ', Vilnius',
        ]);
        $createdBuildings[] = $building;
    }
    
    // Property: All created buildings should have the admin's tenant_id
    foreach ($createdBuildings as $building) {
        expect($building->tenant_id)->toBe($admin->tenant_id)
            ->and($building->tenant_id)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 6: Resource creation inherits tenant_id
// Validates: Requirements 4.1, 4.4, 13.2
test('admin created properties automatically inherit admin tenant_id', function () {
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
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Create a building first
    $building = Building::factory()->create([
        'name' => fake()->company() . ' Building',
        'address' => fake()->streetAddress() . ', Vilnius',
    ]);
    
    // Create multiple properties
    $propertyCount = fake()->numberBetween(2, 5);
    $createdProperties = [];
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $property = Property::factory()->create([
            'building_id' => $building->id,
            'unit_number' => fake()->numberBetween(1, 100),
        ]);
        $createdProperties[] = $property;
    }
    
    // Property: All created properties should have the admin's tenant_id
    foreach ($createdProperties as $property) {
        expect($property->tenant_id)->toBe($admin->tenant_id)
            ->and($property->tenant_id)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 6: Resource creation inherits tenant_id
// Validates: Requirements 4.1, 4.4, 13.2
test('admin created meters automatically inherit admin tenant_id', function () {
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
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Create a building and property first
    $building = Building::factory()->create([
        'name' => fake()->company() . ' Building',
        'address' => fake()->streetAddress() . ', Vilnius',
    ]);
    
    $property = Property::factory()->create([
        'building_id' => $building->id,
        'unit_number' => fake()->numberBetween(1, 100),
    ]);
    
    // Create multiple meters
    $meterCount = fake()->numberBetween(2, 5);
    $createdMeters = [];
    
    for ($i = 0; $i < $meterCount; $i++) {
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'serial_number' => fake()->unique()->numerify('METER-####'),
        ]);
        $createdMeters[] = $meter;
    }
    
    // Property: All created meters should have the admin's tenant_id
    foreach ($createdMeters as $meter) {
        expect($meter->tenant_id)->toBe($admin->tenant_id)
            ->and($meter->tenant_id)->not->toBeNull();
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 6: Resource creation inherits tenant_id
// Validates: Requirements 4.1, 4.4, 13.2
test('multiple admins create resources with their respective tenant_ids', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admin accounts
    $adminCount = fake()->numberBetween(2, 4);
    $adminResources = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => 'basic',
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        // Act as this admin
        $this->actingAs($admin);
        
        // Create resources
        $building = Building::factory()->create([
            'name' => fake()->company() . ' Building',
            'address' => fake()->streetAddress() . ', Vilnius',
        ]);
        
        $property = Property::factory()->create([
            'building_id' => $building->id,
            'unit_number' => fake()->numberBetween(1, 100),
        ]);
        
        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'serial_number' => fake()->unique()->numerify('METER-####'),
        ]);
        
        $adminResources[] = [
            'admin' => $admin,
            'building' => $building,
            'property' => $property,
            'meter' => $meter,
        ];
    }
    
    // Property: Each admin's resources should have their respective tenant_id
    foreach ($adminResources as $data) {
        $admin = $data['admin'];
        $building = $data['building'];
        $property = $data['property'];
        $meter = $data['meter'];
        
        expect($building->tenant_id)->toBe($admin->tenant_id)
            ->and($property->tenant_id)->toBe($admin->tenant_id)
            ->and($meter->tenant_id)->toBe($admin->tenant_id);
    }
    
    // Property: Different admins should have different tenant_ids
    for ($i = 0; $i < count($adminResources); $i++) {
        for ($j = $i + 1; $j < count($adminResources); $j++) {
            expect($adminResources[$i]['admin']->tenant_id)
                ->not->toBe($adminResources[$j]['admin']->tenant_id);
        }
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 6: Resource creation inherits tenant_id
// Validates: Requirements 4.1, 4.4, 13.2
test('resource creation inherits tenant_id even without explicit assignment', function () {
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
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Create resources WITHOUT explicitly setting tenant_id
    // The system should automatically assign it
    $building = Building::create([
        'name' => fake()->company() . ' Building',
        'address' => fake()->streetAddress() . ', Vilnius',
        'postal_code' => fake()->postcode(),
    ]);
    
    $property = Property::create([
        'building_id' => $building->id,
        'unit_number' => (string) fake()->numberBetween(1, 100),
        'floor' => fake()->numberBetween(1, 10),
        'area_sqm' => fake()->randomFloat(2, 30, 150),
        'property_type' => fake()->randomElement(['apartment', 'house']),
    ]);
    
    $meter = Meter::create([
        'property_id' => $property->id,
        'meter_type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'serial_number' => fake()->unique()->numerify('METER-####'),
        'installation_date' => now()->subDays(fake()->numberBetween(1, 365)),
    ]);
    
    // Property: Resources created without explicit tenant_id should inherit admin's tenant_id
    expect($building->tenant_id)->toBe($admin->tenant_id)
        ->and($property->tenant_id)->toBe($admin->tenant_id)
        ->and($meter->tenant_id)->toBe($admin->tenant_id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 6: Resource creation inherits tenant_id
// Validates: Requirements 4.1, 4.4, 13.2
test('nested resource creation maintains tenant_id consistency', function () {
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
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Create a building
    $building = Building::factory()->create([
        'name' => fake()->company() . ' Building',
        'address' => fake()->streetAddress() . ', Vilnius',
    ]);
    
    // Create multiple properties for the building
    $propertyCount = fake()->numberBetween(2, 4);
    $properties = [];
    
    for ($i = 0; $i < $propertyCount; $i++) {
        $property = Property::factory()->create([
            'building_id' => $building->id,
            'unit_number' => fake()->numberBetween(1, 100),
        ]);
        $properties[] = $property;
        
        // Create multiple meters for each property
        $meterCount = fake()->numberBetween(1, 3);
        for ($j = 0; $j < $meterCount; $j++) {
            $meter = Meter::factory()->create([
                'property_id' => $property->id,
                'serial_number' => fake()->unique()->numerify('METER-####'),
            ]);
            
            // Property: Meter should have the same tenant_id as the admin
            expect($meter->tenant_id)->toBe($admin->tenant_id);
        }
        
        // Property: Property should have the same tenant_id as the admin
        expect($property->tenant_id)->toBe($admin->tenant_id);
    }
    
    // Property: Building should have the same tenant_id as the admin
    expect($building->tenant_id)->toBe($admin->tenant_id);
    
    // Property: All resources in the hierarchy should have consistent tenant_id
    foreach ($properties as $property) {
        expect($property->tenant_id)->toBe($building->tenant_id);
        
        $meters = Meter::where('property_id', $property->id)->get();
        foreach ($meters as $meter) {
            expect($meter->tenant_id)->toBe($property->tenant_id)
                ->and($meter->tenant_id)->toBe($building->tenant_id);
        }
    }
})->repeat(100);

