<?php

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: vilnius-utilities-billing, Property 15: Tenant data isolation
// Validates: Requirements 7.1, 7.2, 7.3, 7.5
test('tenant data isolation - queries automatically filter by session tenant_id', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of properties for each tenant
    $propertiesCount1 = fake()->numberBetween(1, 5);
    $propertiesCount2 = fake()->numberBetween(1, 5);
    
    // Create properties for tenant 1
    $properties1 = [];
    for ($i = 0; $i < $propertiesCount1; $i++) {
        $properties1[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create properties for tenant 2
    $properties2 = [];
    for ($i = 0; $i < $propertiesCount2; $i++) {
        $properties2[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Set session to tenant 1
    session(['tenant_id' => $tenantId1]);
    
    // Property: All queries should only return tenant 1's data
    $queriedProperties = Property::all();
    expect($queriedProperties)->toHaveCount($propertiesCount1);
    
    // Verify all returned properties belong to tenant 1
    foreach ($queriedProperties as $property) {
        expect($property->tenant_id)->toBe($tenantId1);
    }
    
    // Verify tenant 2's properties are not accessible
    foreach ($properties2 as $property2) {
        $result = Property::find($property2->id);
        expect($result)->toBeNull();
    }
    
    // Switch session to tenant 2
    session(['tenant_id' => $tenantId2]);
    
    // Property: All queries should now only return tenant 2's data
    $queriedProperties = Property::all();
    expect($queriedProperties)->toHaveCount($propertiesCount2);
    
    // Verify all returned properties belong to tenant 2
    foreach ($queriedProperties as $property) {
        expect($property->tenant_id)->toBe($tenantId2);
    }
    
    // Verify tenant 1's properties are not accessible
    foreach ($properties1 as $property1) {
        $result = Property::find($property1->id);
        expect($result)->toBeNull();
    }
})->repeat(100);

// Feature: vilnius-utilities-billing, Property 15: Tenant data isolation
// Validates: Requirements 7.1, 7.2, 7.3, 7.5
test('tenant data isolation - Meter model enforces tenant filtering', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of meters for each tenant
    $metersCount1 = fake()->numberBetween(1, 5);
    $metersCount2 = fake()->numberBetween(1, 5);
    
    // Create meters for tenant 1
    $meters1 = [];
    for ($i = 0; $i < $metersCount1; $i++) {
        $meters1[] = Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    // Create meters for tenant 2
    $meters2 = [];
    for ($i = 0; $i < $metersCount2; $i++) {
        $meters2[] = Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    // Set session to tenant 1
    session(['tenant_id' => $tenantId1]);
    
    // Property: All queries should only return tenant 1's meters
    $queriedMeters = Meter::all();
    expect($queriedMeters)->toHaveCount($metersCount1);
    
    // Verify all returned meters belong to tenant 1
    foreach ($queriedMeters as $meter) {
        expect($meter->tenant_id)->toBe($tenantId1);
    }
    
    // Verify tenant 2's meters are not accessible
    foreach ($meters2 as $meter2) {
        $result = Meter::find($meter2->id);
        expect($result)->toBeNull();
    }
    
    // Switch session to tenant 2
    session(['tenant_id' => $tenantId2]);
    
    // Property: All queries should now only return tenant 2's meters
    $queriedMeters = Meter::all();
    expect($queriedMeters)->toHaveCount($metersCount2);
    
    // Verify all returned meters belong to tenant 2
    foreach ($queriedMeters as $meter) {
        expect($meter->tenant_id)->toBe($tenantId2);
    }
    
    // Verify tenant 1's meters are not accessible
    foreach ($meters1 as $meter1) {
        $result = Meter::find($meter1->id);
        expect($result)->toBeNull();
    }
})->repeat(100);

// Feature: vilnius-utilities-billing, Property 15: Tenant data isolation
// Validates: Requirements 7.1, 7.2, 7.3, 7.5
test('tenant data isolation - Invoice model enforces tenant filtering', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of invoices for each tenant
    $invoicesCount1 = fake()->numberBetween(1, 5);
    $invoicesCount2 = fake()->numberBetween(1, 5);
    
    // Create invoices for tenant 1
    $invoices1 = [];
    for ($i = 0; $i < $invoicesCount1; $i++) {
        $invoices1[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'billing_period_start' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'billing_period_end' => fake()->dateTimeBetween('-1 month', 'now'),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement(['draft', 'finalized', 'paid']),
        ]);
    }
    
    // Create invoices for tenant 2
    $invoices2 = [];
    for ($i = 0; $i < $invoicesCount2; $i++) {
        $invoices2[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'billing_period_start' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'billing_period_end' => fake()->dateTimeBetween('-1 month', 'now'),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement(['draft', 'finalized', 'paid']),
        ]);
    }
    
    // Set session to tenant 1
    session(['tenant_id' => $tenantId1]);
    
    // Property: All queries should only return tenant 1's invoices
    $queriedInvoices = Invoice::all();
    expect($queriedInvoices)->toHaveCount($invoicesCount1);
    
    // Verify all returned invoices belong to tenant 1
    foreach ($queriedInvoices as $invoice) {
        expect($invoice->tenant_id)->toBe($tenantId1);
    }
    
    // Verify tenant 2's invoices are not accessible
    foreach ($invoices2 as $invoice2) {
        $result = Invoice::find($invoice2->id);
        expect($result)->toBeNull();
    }
    
    // Switch session to tenant 2
    session(['tenant_id' => $tenantId2]);
    
    // Property: All queries should now only return tenant 2's invoices
    $queriedInvoices = Invoice::all();
    expect($queriedInvoices)->toHaveCount($invoicesCount2);
    
    // Verify all returned invoices belong to tenant 2
    foreach ($queriedInvoices as $invoice) {
        expect($invoice->tenant_id)->toBe($tenantId2);
    }
    
    // Verify tenant 1's invoices are not accessible
    foreach ($invoices1 as $invoice1) {
        $result = Invoice::find($invoice1->id);
        expect($result)->toBeNull();
    }
})->repeat(100);

// Feature: vilnius-utilities-billing, Property 15: Tenant data isolation
// Validates: Requirements 7.1, 7.2, 7.3, 7.5
test('tenant data isolation - MeterReading model enforces tenant filtering', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a user for entered_by field
    $user = User::factory()->create(['tenant_id' => $tenantId1]);
    
    // Create random number of meter readings for each tenant
    $readingsCount1 = fake()->numberBetween(1, 5);
    $readingsCount2 = fake()->numberBetween(1, 5);
    
    // Create meter readings for tenant 1
    $readings1 = [];
    for ($i = 0; $i < $readingsCount1; $i++) {
        $readings1[] = MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'meter_id' => fake()->numberBetween(1, 100),
            'reading_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'value' => fake()->randomFloat(2, 0, 10000),
            'entered_by' => $user->id,
        ]);
    }
    
    // Create meter readings for tenant 2
    $readings2 = [];
    for ($i = 0; $i < $readingsCount2; $i++) {
        $readings2[] = MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'meter_id' => fake()->numberBetween(1, 100),
            'reading_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'value' => fake()->randomFloat(2, 0, 10000),
            'entered_by' => $user->id,
        ]);
    }
    
    // Set session to tenant 1
    session(['tenant_id' => $tenantId1]);
    
    // Property: All queries should only return tenant 1's readings
    $queriedReadings = MeterReading::all();
    expect($queriedReadings)->toHaveCount($readingsCount1);
    
    // Verify all returned readings belong to tenant 1
    foreach ($queriedReadings as $reading) {
        expect($reading->tenant_id)->toBe($tenantId1);
    }
    
    // Verify tenant 2's readings are not accessible
    foreach ($readings2 as $reading2) {
        $result = MeterReading::find($reading2->id);
        expect($result)->toBeNull();
    }
    
    // Switch session to tenant 2
    session(['tenant_id' => $tenantId2]);
    
    // Property: All queries should now only return tenant 2's readings
    $queriedReadings = MeterReading::all();
    expect($queriedReadings)->toHaveCount($readingsCount2);
    
    // Verify all returned readings belong to tenant 2
    foreach ($queriedReadings as $reading) {
        expect($reading->tenant_id)->toBe($tenantId2);
    }
    
    // Verify tenant 1's readings are not accessible
    foreach ($readings1 as $reading1) {
        $result = MeterReading::find($reading1->id);
        expect($result)->toBeNull();
    }
})->repeat(100);

// Feature: vilnius-utilities-billing, Property 15: Tenant data isolation
// Validates: Requirements 7.1, 7.2, 7.3, 7.5
test('tenant data isolation - Tenant model enforces tenant filtering', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of tenants for each tenant_id
    $tenantsCount1 = fake()->numberBetween(1, 5);
    $tenantsCount2 = fake()->numberBetween(1, 5);
    
    // Create tenants for tenant 1
    $tenants1 = [];
    for ($i = 0; $i < $tenantsCount1; $i++) {
        $tenants1[] = Tenant::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'lease_start' => fake()->dateTimeBetween('-1 year', 'now'),
            'lease_end' => fake()->dateTimeBetween('now', '+2 years'),
        ]);
    }
    
    // Create tenants for tenant 2
    $tenants2 = [];
    for ($i = 0; $i < $tenantsCount2; $i++) {
        $tenants2[] = Tenant::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'lease_start' => fake()->dateTimeBetween('-1 year', 'now'),
            'lease_end' => fake()->dateTimeBetween('now', '+2 years'),
        ]);
    }
    
    // Set session to tenant 1
    session(['tenant_id' => $tenantId1]);
    
    // Property: All queries should only return tenant 1's tenants
    $queriedTenants = Tenant::all();
    expect($queriedTenants)->toHaveCount($tenantsCount1);
    
    // Verify all returned tenants belong to tenant 1
    foreach ($queriedTenants as $tenant) {
        expect($tenant->tenant_id)->toBe($tenantId1);
    }
    
    // Verify tenant 2's tenants are not accessible
    foreach ($tenants2 as $tenant2) {
        $result = Tenant::find($tenant2->id);
        expect($result)->toBeNull();
    }
    
    // Switch session to tenant 2
    session(['tenant_id' => $tenantId2]);
    
    // Property: All queries should now only return tenant 2's tenants
    $queriedTenants = Tenant::all();
    expect($queriedTenants)->toHaveCount($tenantsCount2);
    
    // Verify all returned tenants belong to tenant 2
    foreach ($queriedTenants as $tenant) {
        expect($tenant->tenant_id)->toBe($tenantId2);
    }
    
    // Verify tenant 1's tenants are not accessible
    foreach ($tenants1 as $tenant1) {
        $result = Tenant::find($tenant1->id);
        expect($result)->toBeNull();
    }
})->repeat(100);
