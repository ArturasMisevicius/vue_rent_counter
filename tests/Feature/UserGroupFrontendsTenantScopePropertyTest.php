<?php

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: user-group-frontends, Property 6: Tenant scope filtering
// Validates: Requirements 5.1, 9.3, 10.1, 11.5
test('tenant scoped resources are automatically filtered by tenant_id', function () {
    // Generate two random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of resources for each tenant
    $resourceCount1 = fake()->numberBetween(2, 8);
    $resourceCount2 = fake()->numberBetween(2, 8);
    
    // Create properties for both tenants
    $properties1 = [];
    for ($i = 0; $i < $resourceCount1; $i++) {
        $properties1[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    $properties2 = [];
    for ($i = 0; $i < $resourceCount2; $i++) {
        $properties2[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create meters for both tenants
    $meters1 = [];
    for ($i = 0; $i < $resourceCount1; $i++) {
        $meters1[] = Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'property_id' => $properties1[$i]->id,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    $meters2 = [];
    for ($i = 0; $i < $resourceCount2; $i++) {
        $meters2[] = Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'property_id' => $properties2[$i]->id,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    // Create users for entered_by field
    $user1 = User::factory()->create(['tenant_id' => $tenantId1, 'role' => UserRole::MANAGER]);
    $user2 = User::factory()->create(['tenant_id' => $tenantId2, 'role' => UserRole::MANAGER]);
    
    // Create tenants (renters) for invoices
    $tenants1 = [];
    for ($i = 0; $i < $resourceCount1; $i++) {
        $tenants1[] = \App\Models\Tenant::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'property_id' => $properties1[$i]->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'lease_start' => fake()->dateTimeBetween('-1 year', 'now'),
            'lease_end' => fake()->dateTimeBetween('now', '+2 years'),
        ]);
    }
    
    $tenants2 = [];
    for ($i = 0; $i < $resourceCount2; $i++) {
        $tenants2[] = \App\Models\Tenant::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'property_id' => $properties2[$i]->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'lease_start' => fake()->dateTimeBetween('-1 year', 'now'),
            'lease_end' => fake()->dateTimeBetween('now', '+2 years'),
        ]);
    }
    
    // Create meter readings for both tenants
    $readings1 = [];
    for ($i = 0; $i < $resourceCount1; $i++) {
        $readings1[] = MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'meter_id' => $meters1[$i]->id,
            'reading_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'value' => fake()->randomFloat(2, 0, 10000),
            'entered_by' => $user1->id,
        ]);
    }
    
    $readings2 = [];
    for ($i = 0; $i < $resourceCount2; $i++) {
        $readings2[] = MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'meter_id' => $meters2[$i]->id,
            'reading_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'value' => fake()->randomFloat(2, 0, 10000),
            'entered_by' => $user2->id,
        ]);
    }
    
    // Create invoices for both tenants
    $invoices1 = [];
    for ($i = 0; $i < $resourceCount1; $i++) {
        $invoices1[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'tenant_renter_id' => $tenants1[$i]->id,
            'billing_period_start' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'billing_period_end' => fake()->dateTimeBetween('-1 month', 'now'),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement(['draft', 'finalized', 'paid']),
        ]);
    }
    
    $invoices2 = [];
    for ($i = 0; $i < $resourceCount2; $i++) {
        $invoices2[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'tenant_renter_id' => $tenants2[$i]->id,
            'billing_period_start' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'billing_period_end' => fake()->dateTimeBetween('-1 month', 'now'),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement(['draft', 'finalized', 'paid']),
        ]);
    }
    
    // Act as user from tenant1
    $this->actingAs($user1);
    
    // Property: All queries should automatically filter to tenant1's resources
    
    // Test Properties
    $queriedProperties = Property::all();
    expect($queriedProperties)->toHaveCount($resourceCount1);
    expect($queriedProperties)->each(fn ($property) => 
        expect($property->tenant_id)->toBe($tenantId1)
    );
    
    // Test Meters
    $queriedMeters = Meter::all();
    expect($queriedMeters)->toHaveCount($resourceCount1);
    expect($queriedMeters)->each(fn ($meter) => 
        expect($meter->tenant_id)->toBe($tenantId1)
    );
    
    // Test MeterReadings
    $queriedReadings = MeterReading::all();
    expect($queriedReadings)->toHaveCount($resourceCount1);
    expect($queriedReadings)->each(fn ($reading) => 
        expect($reading->tenant_id)->toBe($tenantId1)
    );
    
    // Test Invoices
    $queriedInvoices = Invoice::all();
    expect($queriedInvoices)->toHaveCount($resourceCount1);
    expect($queriedInvoices)->each(fn ($invoice) => 
        expect($invoice->tenant_id)->toBe($tenantId1)
    );
    
    // Verify tenant2's resources are not accessible via find()
    foreach ($properties2 as $property2) {
        expect(Property::find($property2->id))->toBeNull();
    }
    foreach ($meters2 as $meter2) {
        expect(Meter::find($meter2->id))->toBeNull();
    }
    foreach ($readings2 as $reading2) {
        expect(MeterReading::find($reading2->id))->toBeNull();
    }
    foreach ($invoices2 as $invoice2) {
        expect(Invoice::find($invoice2->id))->toBeNull();
    }
    
    // Switch to tenant2
    $this->actingAs($user2);
    
    // Property: All queries should now automatically filter to tenant2's resources
    
    // Test Properties
    $queriedProperties = Property::all();
    expect($queriedProperties)->toHaveCount($resourceCount2);
    expect($queriedProperties)->each(fn ($property) => 
        expect($property->tenant_id)->toBe($tenantId2)
    );
    
    // Test Meters
    $queriedMeters = Meter::all();
    expect($queriedMeters)->toHaveCount($resourceCount2);
    expect($queriedMeters)->each(fn ($meter) => 
        expect($meter->tenant_id)->toBe($tenantId2)
    );
    
    // Test MeterReadings
    $queriedReadings = MeterReading::all();
    expect($queriedReadings)->toHaveCount($resourceCount2);
    expect($queriedReadings)->each(fn ($reading) => 
        expect($reading->tenant_id)->toBe($tenantId2)
    );
    
    // Test Invoices
    $queriedInvoices = Invoice::all();
    expect($queriedInvoices)->toHaveCount($resourceCount2);
    expect($queriedInvoices)->each(fn ($invoice) => 
        expect($invoice->tenant_id)->toBe($tenantId2)
    );
    
    // Verify tenant1's resources are not accessible via find()
    foreach ($properties1 as $property1) {
        expect(Property::find($property1->id))->toBeNull();
    }
    foreach ($meters1 as $meter1) {
        expect(Meter::find($meter1->id))->toBeNull();
    }
    foreach ($readings1 as $reading1) {
        expect(MeterReading::find($reading1->id))->toBeNull();
    }
    foreach ($invoices1 as $invoice1) {
        expect(Invoice::find($invoice1->id))->toBeNull();
    }
})->repeat(100);
