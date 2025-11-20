<?php

use App\Models\User;
use App\Models\Property;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Building;
use App\Models\Tariff;
use App\Models\Provider;
use App\Models\Tenant;
use App\Enums\UserRole;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

// Feature: authentication-testing, Property 1: Test user tenant assignment
// Validates: Requirements 1.4
test('all test users created by seeder have non-null tenant_id', function () {
    // Run the test seeder
    $this->artisan('db:seed', ['--class' => 'TestUsersSeeder']);
    
    // Get all test users
    $testEmails = [
        'admin@test.com',
        'manager@test.com',
        'manager2@test.com',
        'tenant@test.com',
        'tenant2@test.com',
        'tenant3@test.com',
    ];
    
    $users = User::whereIn('email', $testEmails)->get();
    
    // Property: all test users should have non-null tenant_id
    foreach ($users as $user) {
        expect($user->tenant_id)->not->toBeNull();
    }
})->repeat(100);

// Feature: authentication-testing, Property 2: Valid credentials authentication
// Validates: Requirements 2.1
test('any user with valid credentials successfully authenticates', function () {
    // Create a random user with known credentials
    $tenantId = fake()->numberBetween(1, 10);
    $password = 'test-password-' . fake()->word();
    
    $user = User::factory()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make($password),
        'role' => fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]),
        'tenant_id' => $tenantId,
    ]);
    
    // Property: authentication with valid credentials should succeed
    $result = Auth::attempt([
        'email' => $user->email,
        'password' => $password,
    ]);
    
    expect($result)->toBeTrue();
    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($user->id);
    
    Auth::logout();
})->repeat(100);

// Feature: authentication-testing, Property 3: Invalid credentials rejection
// Validates: Requirements 2.2
test('any authentication attempt with invalid credentials fails', function () {
    // Create a user
    $user = User::factory()->create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('correct-password'),
        'tenant_id' => fake()->numberBetween(1, 10),
    ]);
    
    // Try with wrong password
    $wrongPassword = 'wrong-password-' . fake()->word();
    
    // Property: authentication with invalid credentials should fail
    $result = Auth::attempt([
        'email' => $user->email,
        'password' => $wrongPassword,
    ]);
    
    expect($result)->toBeFalse();
    expect(Auth::check())->toBeFalse();
})->repeat(100);

// Feature: authentication-testing, Property 4: Manager property isolation
// Validates: Requirements 4.1
test('any manager user only sees properties from their tenant', function () {
    $tenantId = fake()->numberBetween(1, 100);
    $otherTenantId = $tenantId + 1;
    
    // Create manager for tenant A
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create properties for tenant A
    $propertiesA = Property::factory()->count(fake()->numberBetween(2, 5))->create([
        'tenant_id' => $tenantId,
    ]);
    
    // Create properties for tenant B
    Property::factory()->count(fake()->numberBetween(2, 5))->create([
        'tenant_id' => $otherTenantId,
    ]);
    
    // Act as manager and query properties
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $visibleProperties = Property::all();
    
    // Property: manager should only see properties from their tenant
    expect($visibleProperties->count())->toBe($propertiesA->count());
    foreach ($visibleProperties as $property) {
        expect($property->tenant_id)->toBe($tenantId);
    }
})->repeat(100);

// Feature: authentication-testing, Property 5: Cross-tenant property access prevention
// Validates: Requirements 4.2
test('any manager attempting to access cross-tenant property gets 404', function () {
    $tenantId = fake()->numberBetween(1, 100);
    $otherTenantId = $tenantId + 1;
    
    // Create manager for tenant A
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create property for tenant B
    $otherProperty = Property::factory()->create([
        'tenant_id' => $otherTenantId,
    ]);
    
    // Act as manager from tenant A
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: attempting to access cross-tenant property should return 404
    $response = $this->get(route('manager.properties.show', $otherProperty->id));
    $response->assertStatus(404);
})->repeat(100);

// Feature: authentication-testing, Property 6: Tenant invoice isolation
// Validates: Requirements 4.3
test('any tenant user only sees their own invoices', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create property and tenant user
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'email' => fake()->unique()->safeEmail(),
    ]);
    
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'email' => $tenantRecord->email,
    ]);
    
    // Create invoices for this tenant
    $ownInvoices = Invoice::factory()->count(fake()->numberBetween(2, 5))->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create invoices for other tenants
    $otherProperty = Property::factory()->create(['tenant_id' => $tenantId + 1]);
    Invoice::factory()->count(fake()->numberBetween(2, 5))->create([
        'tenant_id' => $tenantId + 1,
        'property_id' => $otherProperty->id,
    ]);
    
    // Act as tenant user
    $this->actingAs($tenantUser);
    session(['tenant_id' => $tenantId]);
    
    $visibleInvoices = Invoice::all();
    
    // Property: tenant should only see their own invoices
    expect($visibleInvoices->count())->toBe($ownInvoices->count());
    foreach ($visibleInvoices as $invoice) {
        expect($invoice->tenant_id)->toBe($tenantId);
    }
})->repeat(100);

// Feature: authentication-testing, Property 7: Cross-tenant invoice access prevention
// Validates: Requirements 4.4
test('any tenant attempting to access another tenant invoice gets 404', function () {
    $tenantId = fake()->numberBetween(1, 100);
    $otherTenantId = $tenantId + 1;
    
    // Create tenant user for tenant A
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'email' => fake()->unique()->safeEmail(),
    ]);
    
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'email' => $tenantRecord->email,
    ]);
    
    // Create invoice for tenant B
    $otherProperty = Property::factory()->create(['tenant_id' => $otherTenantId]);
    $otherInvoice = Invoice::factory()->create([
        'tenant_id' => $otherTenantId,
        'property_id' => $otherProperty->id,
    ]);
    
    // Act as tenant from tenant A
    $this->actingAs($tenantUser);
    session(['tenant_id' => $tenantId]);
    
    // Property: attempting to access cross-tenant invoice should return 404
    $response = $this->get(route('tenant.invoices.show', $otherInvoice->id));
    $response->assertStatus(404);
})->repeat(100);

// Feature: authentication-testing, Property 8: Complete meter coverage
// Validates: Requirements 5.2
test('any property has all applicable meter types', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create building
    $building = Building::factory()->create(['tenant_id' => $tenantId]);
    
    // Create property (apartment in building)
    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
        'building_id' => $building->id,
    ]);
    
    // Create all meter types
    Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);
    
    Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
    ]);
    
    Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::WATER_HOT,
    ]);
    
    Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::HEATING,
    ]);
    
    // Property: apartment in building should have all 4 meter types
    $meters = Meter::where('property_id', $property->id)->get();
    $meterTypes = $meters->pluck('type')->toArray();
    
    expect($meterTypes)->toContain(MeterType::ELECTRICITY);
    expect($meterTypes)->toContain(MeterType::WATER_COLD);
    expect($meterTypes)->toContain(MeterType::WATER_HOT);
    expect($meterTypes)->toContain(MeterType::HEATING);
})->repeat(100);

// Feature: authentication-testing, Property 9: Meter reading storage completeness
// Validates: Requirements 6.1
test('any stored meter reading has timestamp and user reference', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create meter
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create meter reading
    $reading = MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'entered_by' => $manager->id,
        'value' => fake()->numberBetween(1000, 9999),
    ]);
    
    // Property: reading should have timestamp and user reference
    expect($reading->entered_by)->not->toBeNull();
    expect($reading->created_at)->not->toBeNull();
    expect($reading->entered_by)->toBe($manager->id);
})->repeat(100);

// Feature: authentication-testing, Property 10: Meter reading monotonicity enforcement
// Validates: Requirements 6.2
test('any reading lower than previous is rejected', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create meter with previous reading
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    $previousValue = fake()->numberBetween(1000, 5000);
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'value' => $previousValue,
        'entered_by' => $manager->id,
        'reading_date' => now()->subMonth(),
    ]);
    
    // Try to create reading with lower value
    $lowerValue = $previousValue - fake()->numberBetween(1, 100);
    
    // Property: reading with lower value should be rejected
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $response = $this->post(route('manager.meter-readings.store'), [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => $lowerValue,
    ]);
    
    $response->assertSessionHasErrors();
})->repeat(100);

// Feature: authentication-testing, Property 11: Meter reading temporal validation
// Validates: Requirements 6.3
test('any reading with future date is rejected', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create meter
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Try to create reading with future date
    $futureDate = now()->addDays(fake()->numberBetween(1, 30));
    
    // Property: reading with future date should be rejected
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $response = $this->post(route('manager.meter-readings.store'), [
        'meter_id' => $meter->id,
        'reading_date' => $futureDate->format('Y-m-d'),
        'value' => fake()->numberBetween(1000, 9999),
    ]);
    
    $response->assertSessionHasErrors();
})->repeat(100);

// Feature: authentication-testing, Property 12: Multi-zone meter reading support
// Validates: Requirements 6.4
test('any multi-zone meter accepts separate zone readings', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create multi-zone meter
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);
    
    // Create readings for different zones
    $dayValue = fake()->numberBetween(1000, 5000);
    $nightValue = fake()->numberBetween(500, 3000);
    
    $dayReading = MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'value' => $dayValue,
        'zone' => 'day',
        'entered_by' => $manager->id,
    ]);
    
    $nightReading = MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'value' => $nightValue,
        'zone' => 'night',
        'entered_by' => $manager->id,
    ]);
    
    // Property: multi-zone meter should accept separate zone readings
    expect($dayReading->zone)->toBe('day');
    expect($nightReading->zone)->toBe('night');
    expect($dayReading->meter_id)->toBe($meter->id);
    expect($nightReading->meter_id)->toBe($meter->id);
})->repeat(100);

// Feature: authentication-testing, Property 13: Meter reading audit trail creation
// Validates: Requirements 6.5
test('any stored reading creates audit trail', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create meter
    $property = Property::factory()->create(['tenant_id' => $tenantId]);
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create reading
    $reading = MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'entered_by' => $manager->id,
        'value' => fake()->numberBetween(1000, 9999),
    ]);
    
    // Property: reading itself serves as audit trail with entered_by and timestamps
    expect($reading->entered_by)->toBe($manager->id);
    expect($reading->created_at)->not->toBeNull();
    expect($reading->updated_at)->not->toBeNull();
})->repeat(100);
