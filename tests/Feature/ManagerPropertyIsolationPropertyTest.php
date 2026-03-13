<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionPlanType;
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

// Feature: hierarchical-user-management, Property 2: Admin tenant isolation
// Validates: Requirements 3.3, 4.3, 12.3
test('admin can only see data within their tenant_id scope', function () {
    // Create a superadmin to create admin accounts
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admin accounts with different tenant_ids
    $adminCount = fake()->numberBetween(2, 4);
    $admins = [];
    $adminResources = [];
    
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
        $admins[] = $admin;
        
        // Create resources for this admin
        $buildingCount = fake()->numberBetween(1, 3);
        $buildings = [];
        $properties = [];
        $meters = [];
        $meterReadings = [];
        $invoices = [];
        
        for ($j = 0; $j < $buildingCount; $j++) {
            $building = Building::factory()->create([
                'tenant_id' => $admin->tenant_id,
            ]);
            $buildings[] = $building->id;
            
            $propertyCount = fake()->numberBetween(1, 2);
            for ($k = 0; $k < $propertyCount; $k++) {
                $property = Property::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'building_id' => $building->id,
                ]);
                $properties[] = $property->id;
                
                $meter = Meter::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'property_id' => $property->id,
                ]);
                $meters[] = $meter->id;
                
                $meterReading = MeterReading::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'meter_id' => $meter->id,
                ]);
                $meterReadings[] = $meterReading->id;
                
                // Create a tenant for the invoice
                $tenant = \App\Models\Tenant::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'property_id' => $property->id,
                ]);
                
                $invoice = Invoice::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'tenant_renter_id' => $tenant->id,
                ]);
                $invoices[] = $invoice->id;
            }
        }
        
        $adminResources[$admin->id] = [
            'tenant_id' => $admin->tenant_id,
            'buildings' => $buildings,
            'properties' => $properties,
            'meters' => $meters,
            'meterReadings' => $meterReadings,
            'invoices' => $invoices,
        ];
    }
    
    // Test each admin can only see their own data
    foreach ($admins as $admin) {
        $this->actingAs($admin);
        
        $expectedResources = $adminResources[$admin->id];
        
        // Property: Admin should see only their buildings
        $buildings = Building::all();
        expect($buildings->pluck('id')->sort()->values()->toArray())
            ->toBe(collect($expectedResources['buildings'])->sort()->values()->toArray());
        
        // Verify all buildings have the correct tenant_id
        foreach ($buildings as $building) {
            expect($building->tenant_id)->toBe($admin->tenant_id);
        }
        
        // Property: Admin should see only their properties
        $properties = Property::all();
        expect($properties->pluck('id')->sort()->values()->toArray())
            ->toBe(collect($expectedResources['properties'])->sort()->values()->toArray());
        
        // Verify all properties have the correct tenant_id
        foreach ($properties as $property) {
            expect($property->tenant_id)->toBe($admin->tenant_id);
        }
        
        // Property: Admin should see only their meters
        $meters = Meter::all();
        expect($meters->pluck('id')->sort()->values()->toArray())
            ->toBe(collect($expectedResources['meters'])->sort()->values()->toArray());
        
        // Verify all meters have the correct tenant_id
        foreach ($meters as $meter) {
            expect($meter->tenant_id)->toBe($admin->tenant_id);
        }
        
        // Property: Admin should see only their meter readings
        $meterReadings = MeterReading::all();
        expect($meterReadings->pluck('id')->sort()->values()->toArray())
            ->toBe(collect($expectedResources['meterReadings'])->sort()->values()->toArray());
        
        // Verify all meter readings have the correct tenant_id
        foreach ($meterReadings as $meterReading) {
            expect($meterReading->tenant_id)->toBe($admin->tenant_id);
        }
        
        // Property: Admin should see only their invoices
        $invoices = Invoice::all();
        expect($invoices->pluck('id')->sort()->values()->toArray())
            ->toBe(collect($expectedResources['invoices'])->sort()->values()->toArray());
        
        // Verify all invoices have the correct tenant_id
        foreach ($invoices as $invoice) {
            expect($invoice->tenant_id)->toBe($admin->tenant_id);
        }
        
        // Property: Admin should NOT see resources from other admins
        foreach ($admins as $otherAdmin) {
            if ($otherAdmin->id === $admin->id) {
                continue;
            }
            
            $otherResources = $adminResources[$otherAdmin->id];
            
            // Verify other admin's buildings are not accessible
            foreach ($otherResources['buildings'] as $buildingId) {
                $result = Building::find($buildingId);
                expect($result)->toBeNull();
            }
            
            // Verify other admin's properties are not accessible
            foreach ($otherResources['properties'] as $propertyId) {
                $result = Property::find($propertyId);
                expect($result)->toBeNull();
            }
            
            // Verify other admin's meters are not accessible
            foreach ($otherResources['meters'] as $meterId) {
                $result = Meter::find($meterId);
                expect($result)->toBeNull();
            }
            
            // Verify other admin's meter readings are not accessible
            foreach ($otherResources['meterReadings'] as $meterReadingId) {
                $result = MeterReading::find($meterReadingId);
                expect($result)->toBeNull();
            }
            
            // Verify other admin's invoices are not accessible
            foreach ($otherResources['invoices'] as $invoiceId) {
                $result = Invoice::find($invoiceId);
                expect($result)->toBeNull();
            }
        }
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 2: Admin tenant isolation
// Validates: Requirements 3.3, 4.3, 12.3
test('admin queries with where clauses only return data from their tenant_id', function () {
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
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Create buildings with the same address for both admins
    $buildingAddress = fake()->unique()->streetAddress() . ', Vilnius';
    
    $building1 = Building::factory()->create([
        'tenant_id' => $admin1->tenant_id,
        'address' => $buildingAddress,
    ]);
    
    $building2 = Building::factory()->create([
        'tenant_id' => $admin2->tenant_id,
        'address' => $buildingAddress,
    ]);
    
    // Act as admin1
    $this->actingAs($admin1);
    
    // Property: Admin1 should only see their building with the address
    $buildings = Building::where('address', $buildingAddress)->get();
    expect($buildings)->toHaveCount(1)
        ->and($buildings->first()->id)->toBe($building1->id)
        ->and($buildings->first()->tenant_id)->toBe($admin1->tenant_id);
    
    // Act as admin2
    $this->actingAs($admin2);
    
    // Property: Admin2 should only see their building with the address
    $buildings = Building::where('address', $buildingAddress)->get();
    expect($buildings)->toHaveCount(1)
        ->and($buildings->first()->id)->toBe($building2->id)
        ->and($buildings->first()->tenant_id)->toBe($admin2->tenant_id);
})->repeat(100);

// Feature: hierarchical-user-management, Property 2: Admin tenant isolation
// Validates: Requirements 3.3, 4.3, 12.3
test('admin cannot access specific resources by id from other tenants', function () {
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
    $allResources = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
            'plan_type' => SubscriptionPlanType::BASIC->value,
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        $property = Property::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'building_id' => $building->id,
        ]);
        $meter = Meter::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property->id,
        ]);
        
        $allResources[] = [
            'admin' => $admin,
            'building_id' => $building->id,
            'property_id' => $property->id,
            'meter_id' => $meter->id,
        ];
    }
    
    // Test each admin cannot access other admins' resources
    foreach ($allResources as $currentResource) {
        $this->actingAs($currentResource['admin']);
        
        foreach ($allResources as $otherResource) {
            if ($currentResource['admin']->id === $otherResource['admin']->id) {
                // Admin should be able to find their own resources
                expect(Building::find($currentResource['building_id']))->not->toBeNull();
                expect(Property::find($currentResource['property_id']))->not->toBeNull();
                expect(Meter::find($currentResource['meter_id']))->not->toBeNull();
            } else {
                // Property: Admin should NOT be able to find other admins' resources
                expect(Building::find($otherResource['building_id']))->toBeNull();
                expect(Property::find($otherResource['property_id']))->toBeNull();
                expect(Meter::find($otherResource['meter_id']))->toBeNull();
            }
        }
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 2: Admin tenant isolation
// Validates: Requirements 3.3, 4.3, 12.3
test('admin tenant isolation works with complex queries and relationships', function () {
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
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin1 = $accountService->createAdminAccount($admin1Data, $superadmin);
    
    $admin2Data = [
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'name' => fake()->name(),
        'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addDays(365)->toDateString(),
    ];
    
    $admin2 = $accountService->createAdminAccount($admin2Data, $superadmin);
    
    // Create resources for admin1
    $building1 = Building::factory()->create(['tenant_id' => $admin1->tenant_id]);
    $property1Count = fake()->numberBetween(2, 4);
    $admin1PropertyIds = [];
    
    for ($i = 0; $i < $property1Count; $i++) {
        $property = Property::factory()->create([
            'tenant_id' => $admin1->tenant_id,
            'building_id' => $building1->id,
        ]);
        $admin1PropertyIds[] = $property->id;
        
        Meter::factory()->create([
            'tenant_id' => $admin1->tenant_id,
            'property_id' => $property->id,
        ]);
    }
    
    // Create resources for admin2
    $building2 = Building::factory()->create(['tenant_id' => $admin2->tenant_id]);
    $property2Count = fake()->numberBetween(2, 4);
    $admin2PropertyIds = [];
    
    for ($i = 0; $i < $property2Count; $i++) {
        $property = Property::factory()->create([
            'tenant_id' => $admin2->tenant_id,
            'building_id' => $building2->id,
        ]);
        $admin2PropertyIds[] = $property->id;
        
        Meter::factory()->create([
            'tenant_id' => $admin2->tenant_id,
            'property_id' => $property->id,
        ]);
    }
    
    // Act as admin1
    $this->actingAs($admin1);
    
    // Property: Admin1 should only see their properties with eager loaded relationships
    $propertiesWithMeters = Property::with('meters')->get();
    expect($propertiesWithMeters)->toHaveCount($property1Count);
    
    foreach ($propertiesWithMeters as $property) {
        expect($property->tenant_id)->toBe($admin1->tenant_id);
        expect($property->meters)->not->toBeEmpty();
        
        foreach ($property->meters as $meter) {
            expect($meter->tenant_id)->toBe($admin1->tenant_id);
        }
    }
    
    // Property: Admin1 should only see their building with properties
    $buildingsWithProperties = Building::with('properties')->get();
    expect($buildingsWithProperties)->toHaveCount(1)
        ->and($buildingsWithProperties->first()->id)->toBe($building1->id);
    
    $properties = $buildingsWithProperties->first()->properties;
    expect($properties)->toHaveCount($property1Count);
    
    foreach ($properties as $property) {
        expect($property->tenant_id)->toBe($admin1->tenant_id);
    }
    
    // Act as admin2
    $this->actingAs($admin2);
    
    // Property: Admin2 should only see their properties with eager loaded relationships
    $propertiesWithMeters = Property::with('meters')->get();
    expect($propertiesWithMeters)->toHaveCount($property2Count);
    
    foreach ($propertiesWithMeters as $property) {
        expect($property->tenant_id)->toBe($admin2->tenant_id);
        expect($property->meters)->not->toBeEmpty();
        
        foreach ($property->meters as $meter) {
            expect($meter->tenant_id)->toBe($admin2->tenant_id);
        }
    }
    
    // Property: Admin2 should only see their building with properties
    $buildingsWithProperties = Building::with('properties')->get();
    expect($buildingsWithProperties)->toHaveCount(1)
        ->and($buildingsWithProperties->first()->id)->toBe($building2->id);
    
    $properties = $buildingsWithProperties->first()->properties;
    expect($properties)->toHaveCount($property2Count);
    
    foreach ($properties as $property) {
        expect($property->tenant_id)->toBe($admin2->tenant_id);
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 2: Admin tenant isolation
// Validates: Requirements 3.3, 4.3, 12.3
test('admin count queries only count resources within their tenant_id', function () {
    // Create a superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Create the services
    $subscriptionService = app(SubscriptionService::class);
    $accountService = new AccountManagementService($subscriptionService);
    
    // Create multiple admins with varying resource counts
    $adminCount = fake()->numberBetween(2, 4);
    $adminResourceCounts = [];
    
    for ($i = 0; $i < $adminCount; $i++) {
        $adminData = [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'name' => fake()->name(),
            'organization_name' => fake()->company(),
        'plan_type' => SubscriptionPlanType::BASIC->value,
            'expires_at' => now()->addDays(365)->toDateString(),
        ];
        
        $admin = $accountService->createAdminAccount($adminData, $superadmin);
        
        $buildingCount = fake()->numberBetween(1, 3);
        $propertyCount = 0;
        $meterCount = 0;
        
        for ($j = 0; $j < $buildingCount; $j++) {
            $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
            
            $propertiesPerBuilding = fake()->numberBetween(1, 3);
            for ($k = 0; $k < $propertiesPerBuilding; $k++) {
                $property = Property::factory()->create([
                    'tenant_id' => $admin->tenant_id,
                    'building_id' => $building->id,
                ]);
                $propertyCount++;
                
                $metersPerProperty = fake()->numberBetween(1, 2);
                for ($m = 0; $m < $metersPerProperty; $m++) {
                    Meter::factory()->create([
                        'tenant_id' => $admin->tenant_id,
                        'property_id' => $property->id,
                    ]);
                    $meterCount++;
                }
            }
        }
        
        $adminResourceCounts[$admin->id] = [
            'admin' => $admin,
            'buildings' => $buildingCount,
            'properties' => $propertyCount,
            'meters' => $meterCount,
        ];
    }
    
    // Test each admin sees correct counts
    foreach ($adminResourceCounts as $data) {
        $this->actingAs($data['admin']);
        
        // Property: Count queries should only count resources within admin's tenant_id
        expect(Building::count())->toBe($data['buildings']);
        expect(Property::count())->toBe($data['properties']);
        expect(Meter::count())->toBe($data['meters']);
    }
})->repeat(100);
