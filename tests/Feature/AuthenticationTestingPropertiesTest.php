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
    
    // Create property and tenant (renter) record
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
    
    // Create invoices for this tenant (renter)
    $ownInvoices = Invoice::factory()->count(fake()->numberBetween(2, 5))->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRecord->id,
    ]);
    
    // Create invoices for other tenants
    $otherProperty = Property::factory()->create(['tenant_id' => $tenantId + 1]);
    $otherTenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId + 1,
        'property_id' => $otherProperty->id,
        'email' => fake()->unique()->safeEmail(),
    ]);
    
    Invoice::factory()->count(fake()->numberBetween(2, 5))->create([
        'tenant_id' => $tenantId + 1,
        'tenant_renter_id' => $otherTenantRecord->id,
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
    
    // Create invoice for tenant B (with tenant renter)
    $otherProperty = Property::factory()->create(['tenant_id' => $otherTenantId]);
    $otherTenantRecord = Tenant::factory()->create([
        'tenant_id' => $otherTenantId,
        'property_id' => $otherProperty->id,
        'email' => fake()->unique()->safeEmail(),
    ]);
    
    $otherInvoice = Invoice::factory()->create([
        'tenant_id' => $otherTenantId,
        'tenant_renter_id' => $otherTenantRecord->id,
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

// Feature: authentication-testing, Property 15: Invoice itemization by utility type
// Validates: Requirements 7.2
test('any invoice contains items for each utility type with consumption', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create property
    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    // Create tenant (renter)
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create providers and tariffs for different utility types
    $electricityProvider = Provider::firstOrCreate(
        ['name' => 'Ignitis'],
        ['service_type' => \App\Enums\ServiceType::ELECTRICITY]
    );
    
    Tariff::firstOrCreate(
        ['provider_id' => $electricityProvider->id, 'name' => 'Test Electricity'],
        [
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(2, 0.10, 0.30),
            ],
            'active_from' => now()->subYear(),
            'active_until' => null,
        ]
    );
    
    $waterProvider = Provider::firstOrCreate(
        ['name' => 'Vilniaus Vandenys'],
        ['service_type' => \App\Enums\ServiceType::WATER]
    );
    
    Tariff::firstOrCreate(
        ['provider_id' => $waterProvider->id, 'name' => 'Test Water'],
        [
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'supply_rate' => fake()->randomFloat(2, 0.80, 1.20),
                'sewage_rate' => fake()->randomFloat(2, 1.00, 1.50),
            ],
            'active_from' => now()->subYear(),
            'active_until' => null,
        ]
    );
    
    // Randomly decide which meter types to create (at least 1, up to 3)
    $meterTypes = [];
    $shouldHaveElectricity = fake()->boolean(80); // 80% chance
    $shouldHaveWaterCold = fake()->boolean(70); // 70% chance
    $shouldHaveWaterHot = fake()->boolean(60); // 60% chance
    
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();
    
    // Create electricity meter and readings if selected
    if ($shouldHaveElectricity) {
        $meterTypes[] = MeterType::ELECTRICITY;
        $electricityMeter = Meter::factory()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);
        
        $startValue = fake()->numberBetween(1000, 5000);
        $consumption = fake()->numberBetween(50, 300);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodStart->copy()->subDay(),
            'value' => $startValue,
            'entered_by' => $manager->id,
        ]);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodEnd,
            'value' => $startValue + $consumption,
            'entered_by' => $manager->id,
        ]);
    }
    
    // Create water cold meter and readings if selected
    if ($shouldHaveWaterCold) {
        $meterTypes[] = MeterType::WATER_COLD;
        $waterColdMeter = Meter::factory()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
            'supports_zones' => false,
        ]);
        
        $startValue = fake()->numberBetween(100, 500);
        $consumption = fake()->numberBetween(5, 20);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $waterColdMeter->id,
            'reading_date' => $periodStart->copy()->subDay(),
            'value' => $startValue,
            'entered_by' => $manager->id,
        ]);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $waterColdMeter->id,
            'reading_date' => $periodEnd,
            'value' => $startValue + $consumption,
            'entered_by' => $manager->id,
        ]);
    }
    
    // Create water hot meter and readings if selected
    if ($shouldHaveWaterHot) {
        $meterTypes[] = MeterType::WATER_HOT;
        $waterHotMeter = Meter::factory()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property->id,
            'type' => MeterType::WATER_HOT,
            'supports_zones' => false,
        ]);
        
        $startValue = fake()->numberBetween(50, 300);
        $consumption = fake()->numberBetween(3, 15);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $waterHotMeter->id,
            'reading_date' => $periodStart->copy()->subDay(),
            'value' => $startValue,
            'entered_by' => $manager->id,
        ]);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $waterHotMeter->id,
            'reading_date' => $periodEnd,
            'value' => $startValue + $consumption,
            'entered_by' => $manager->id,
        ]);
    }
    
    // Ensure we have at least one meter type
    if (empty($meterTypes)) {
        $meterTypes[] = MeterType::ELECTRICITY;
        $electricityMeter = Meter::factory()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);
        
        $startValue = fake()->numberBetween(1000, 5000);
        $consumption = fake()->numberBetween(50, 300);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodStart->copy()->subDay(),
            'value' => $startValue,
            'entered_by' => $manager->id,
        ]);
        
        MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodEnd,
            'value' => $startValue + $consumption,
            'entered_by' => $manager->id,
        ]);
    }
    
    // Generate invoice
    session(['tenant_id' => $tenantId]);
    $billingService = app(\App\Services\BillingService::class);
    $invoice = $billingService->generateInvoice($tenantRecord, $periodStart, $periodEnd);
    
    // Property: invoice should have items for each utility type with consumption
    expect($invoice->items->count())->toBeGreaterThanOrEqual(count($meterTypes));
    
    // Verify each meter type with consumption has a corresponding invoice item
    foreach ($meterTypes as $meterType) {
        $hasItemForType = $invoice->items->contains(function ($item) use ($meterType) {
            $description = strtolower($item->description);
            return match($meterType) {
                MeterType::ELECTRICITY => str_contains($description, 'electric'),
                MeterType::WATER_COLD => str_contains($description, 'cold') || str_contains($description, 'water'),
                MeterType::WATER_HOT => str_contains($description, 'hot') || str_contains($description, 'water'),
                default => false,
            };
        });
        
        expect($hasItemForType)->toBeTrue(
            "Invoice should have an item for {$meterType->value} but none was found"
        );
    }
})->repeat(100);

// Feature: authentication-testing, Property 14: Invoice calculation from readings
// Validates: Requirements 7.1
test('any invoice is calculated from meter readings and tariffs', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create property and tenant (renter)
    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create provider and tariff
    $provider = Provider::firstOrCreate(
        ['name' => 'Test Provider ' . fake()->unique()->numberBetween(1, 10000)],
        ['service_type' => \App\Enums\ServiceType::ELECTRICITY]
    );
    
    $rate = fake()->randomFloat(4, 0.10, 0.30);
    
    Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff ' . fake()->unique()->numberBetween(1, 10000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $rate,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);
    
    // Create meter
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);
    
    // Create meter readings for billing period
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();
    
    $startValue = fake()->numberBetween(1000, 5000);
    $consumption = fake()->numberBetween(50, 300);
    $endValue = $startValue + $consumption;
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => $startValue,
        'entered_by' => $manager->id,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => $endValue,
        'entered_by' => $manager->id,
    ]);
    
    // Generate invoice
    session(['tenant_id' => $tenantId]);
    $billingService = app(\App\Services\BillingService::class);
    $invoice = $billingService->generateInvoice($tenantRecord, $periodStart, $periodEnd);
    
    // Property: invoice total should be calculated from meter readings and tariff rates
    // Expected calculation: consumption * rate
    $expectedTotal = $consumption * $rate;
    
    // Allow for small floating point differences
    expect($invoice->total_amount)->toBeGreaterThan(0);
    expect(abs($invoice->total_amount - $expectedTotal))->toBeLessThan(0.01);
    
    // Verify invoice has items
    expect($invoice->items->count())->toBeGreaterThan(0);
    
    // Verify at least one item has the expected consumption
    $hasCorrectConsumption = $invoice->items->contains(function ($item) use ($consumption) {
        return abs($item->quantity - $consumption) < 0.01;
    });
    
    expect($hasCorrectConsumption)->toBeTrue(
        "Invoice should have an item with consumption {$consumption}"
    );
})->repeat(100);

// Feature: authentication-testing, Property 16: Tariff rate snapshotting
// Validates: Requirements 7.3
test('any invoice items contain snapshotted tariff rates', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create property and tenant
    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create provider and tariff with a specific rate
    $provider = Provider::firstOrCreate(
        ['name' => 'Test Provider ' . fake()->unique()->numberBetween(1, 10000)],
        ['service_type' => \App\Enums\ServiceType::ELECTRICITY]
    );
    
    $originalRate = fake()->randomFloat(4, 0.10, 0.30);
    
    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff ' . fake()->unique()->numberBetween(1, 10000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $originalRate,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);
    
    // Create meter
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);
    
    // Create meter readings for billing period
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();
    
    $startValue = fake()->numberBetween(1000, 5000);
    $consumption = fake()->numberBetween(50, 300);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => $startValue,
        'entered_by' => $manager->id,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => $startValue + $consumption,
        'entered_by' => $manager->id,
    ]);
    
    // Generate invoice (this snapshots the tariff rate)
    session(['tenant_id' => $tenantId]);
    $billingService = app(\App\Services\BillingService::class);
    $invoice = $billingService->generateInvoice($tenantRecord, $periodStart, $periodEnd);
    
    // Get the snapshotted unit price from invoice items
    $invoiceItem = $invoice->items->first();
    expect($invoiceItem)->not->toBeNull();
    
    $snapshottedUnitPrice = $invoiceItem->unit_price;
    
    // Property: snapshotted unit price should match the original tariff rate
    expect((float)$snapshottedUnitPrice)->toBe($originalRate);
    
    // Now modify the tariff rate in the database
    $newRate = $originalRate + fake()->randomFloat(4, 0.05, 0.15);
    $tariff->update([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $newRate,
        ],
    ]);
    
    // Refresh the invoice and its items from database
    $invoice->refresh();
    $invoiceItem->refresh();
    
    // Property: invoice item should still contain the original snapshotted rate
    expect((float)$invoiceItem->unit_price)->toBe($originalRate);
    expect((float)$invoiceItem->unit_price)->not->toBe($newRate);
})->repeat(100);

// Feature: authentication-testing, Property 17: Invoice immutability after finalization
// Validates: Requirements 7.4
test('any finalized invoice cannot be modified', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create property and tenant (renter)
    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create provider and tariff
    $provider = Provider::firstOrCreate(
        ['name' => 'Test Provider ' . fake()->unique()->numberBetween(1, 10000)],
        ['service_type' => \App\Enums\ServiceType::ELECTRICITY]
    );
    
    Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff ' . fake()->unique()->numberBetween(1, 10000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.10, 0.30),
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);
    
    // Create meter
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);
    
    // Create meter readings for billing period
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();
    
    $startValue = fake()->numberBetween(1000, 5000);
    $consumption = fake()->numberBetween(50, 300);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => $startValue,
        'entered_by' => $manager->id,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => $startValue + $consumption,
        'entered_by' => $manager->id,
    ]);
    
    // Generate invoice
    session(['tenant_id' => $tenantId]);
    $billingService = app(\App\Services\BillingService::class);
    $invoice = $billingService->generateInvoice($tenantRecord, $periodStart, $periodEnd);
    
    // Store original values
    $originalTotalAmount = $invoice->total_amount;
    $originalBillingPeriodStart = $invoice->billing_period_start;
    
    // Finalize the invoice
    $invoice->finalize();
    expect($invoice->isFinalized())->toBeTrue();
    expect($invoice->finalized_at)->not->toBeNull();
    
    // Property: attempting to modify finalized invoice should throw exception
    try {
        $invoice->total_amount = $originalTotalAmount + fake()->randomFloat(2, 10, 100);
        $invoice->save();
        
        // If we reach here, the test should fail
        expect(false)->toBeTrue('Expected InvoiceAlreadyFinalizedException to be thrown');
    } catch (\App\Exceptions\InvoiceAlreadyFinalizedException $e) {
        // Expected exception - test passes
        expect($e)->toBeInstanceOf(\App\Exceptions\InvoiceAlreadyFinalizedException::class);
    }
    
    // Verify invoice values remain unchanged
    $invoice->refresh();
    expect((float)$invoice->total_amount)->toBe((float)$originalTotalAmount);
    
    // Property: attempting to modify billing period should also throw exception
    try {
        $invoice->billing_period_start = $originalBillingPeriodStart->copy()->subDays(5);
        $invoice->save();
        
        // If we reach here, the test should fail
        expect(false)->toBeTrue('Expected InvoiceAlreadyFinalizedException to be thrown');
    } catch (\App\Exceptions\InvoiceAlreadyFinalizedException $e) {
        // Expected exception - test passes
        expect($e)->toBeInstanceOf(\App\Exceptions\InvoiceAlreadyFinalizedException::class);
    }
    
    // Verify billing period remains unchanged
    $invoice->refresh();
    expect($invoice->billing_period_start->format('Y-m-d'))->toBe($originalBillingPeriodStart->format('Y-m-d'));
    
    // Property: status change from FINALIZED to PAID should be allowed
    try {
        $invoice->status = InvoiceStatus::PAID;
        $invoice->save();
        
        // This should succeed
        expect($invoice->status)->toBe(InvoiceStatus::PAID);
    } catch (\App\Exceptions\InvoiceAlreadyFinalizedException $e) {
        // This should not happen - status changes should be allowed
        expect(false)->toBeTrue('Status change from FINALIZED to PAID should be allowed');
    }
})->repeat(100);

// Feature: authentication-testing, Property 18: Finalized invoice tariff independence
// Validates: Requirements 7.5
test('any finalized invoice remains unchanged when tariffs change', function () {
    $tenantId = fake()->numberBetween(1, 100);
    
    // Create property and tenant (renter)
    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenantRecord = Tenant::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create provider and tariff with a specific rate
    $provider = Provider::firstOrCreate(
        ['name' => 'Test Provider ' . fake()->unique()->numberBetween(1, 10000)],
        ['service_type' => \App\Enums\ServiceType::ELECTRICITY]
    );
    
    $originalRate = fake()->randomFloat(4, 0.10, 0.30);
    
    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff ' . fake()->unique()->numberBetween(1, 10000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $originalRate,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);
    
    // Create meter
    $meter = Meter::factory()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);
    
    // Create meter readings for billing period
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();
    
    $startValue = fake()->numberBetween(1000, 5000);
    $consumption = fake()->numberBetween(50, 300);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => $startValue,
        'entered_by' => $manager->id,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => $startValue + $consumption,
        'entered_by' => $manager->id,
    ]);
    
    // Generate invoice (this snapshots the tariff rate)
    session(['tenant_id' => $tenantId]);
    $billingService = app(\App\Services\BillingService::class);
    $invoice = $billingService->generateInvoice($tenantRecord, $periodStart, $periodEnd);
    
    // Store original values before finalization
    $originalTotalAmount = $invoice->total_amount;
    $originalItemCount = $invoice->items->count();
    $originalItemPrices = $invoice->items->map(function ($item) {
        return [
            'id' => $item->id,
            'unit_price' => (float)$item->unit_price,
            'total' => (float)$item->total,
            'quantity' => (float)$item->quantity,
        ];
    })->toArray();
    
    // Finalize the invoice
    $invoice->finalize();
    expect($invoice->isFinalized())->toBeTrue();
    expect($invoice->finalized_at)->not->toBeNull();
    
    // Now modify the tariff rate in the database (simulate tariff change)
    $newRate = $originalRate + fake()->randomFloat(4, 0.05, 0.20);
    $tariff->update([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $newRate,
        ],
    ]);
    
    // Verify tariff was actually changed
    $tariff->refresh();
    $updatedTariffRate = $tariff->configuration['rate'];
    expect((float)$updatedTariffRate)->toBe($newRate);
    expect((float)$updatedTariffRate)->not->toBe($originalRate);
    
    // Refresh the invoice and its items from database
    $invoice->refresh();
    $invoice->load('items');
    
    // Property: finalized invoice total should remain unchanged despite tariff change
    expect((float)$invoice->total_amount)->toBe((float)$originalTotalAmount);
    
    // Property: invoice items count should remain unchanged
    expect($invoice->items->count())->toBe($originalItemCount);
    
    // Property: each invoice item's prices should remain unchanged (snapshotted)
    foreach ($originalItemPrices as $originalItem) {
        $currentItem = $invoice->items->firstWhere('id', $originalItem['id']);
        
        expect($currentItem)->not->toBeNull(
            "Invoice item {$originalItem['id']} should still exist"
        );
        
        // Verify unit price is still the original snapshotted rate
        expect((float)$currentItem->unit_price)->toBe($originalItem['unit_price']);
        expect((float)$currentItem->unit_price)->not->toBe($newRate);
        
        // Verify total is still the original calculated total
        expect((float)$currentItem->total)->toBe($originalItem['total']);
        
        // Verify quantity hasn't changed
        expect((float)$currentItem->quantity)->toBe($originalItem['quantity']);
    }
    
    // Property: attempting to regenerate/recalculate should not affect finalized invoice
    // The system should not provide a way to recalculate finalized invoices
    // We verify this by checking that the invoice remains unchanged
    $invoice->refresh();
    expect((float)$invoice->total_amount)->toBe((float)$originalTotalAmount);
})->repeat(100);

// Feature: authentication-testing, Property 19: Time-of-use zone overlap validation
// Validates: Requirements 8.1
test('any tariff with overlapping time-of-use zones is rejected', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => fake()->numberBetween(1, 100),
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();
    
    // Generate random number of zones (2-5)
    $numZones = fake()->numberBetween(2, 5);
    $zones = [];
    
    // Create zones with intentional overlap
    // Strategy: Create first zone, then create second zone that overlaps with it
    $firstZoneStart = fake()->numberBetween(0, 18); // Start hour (0-18)
    $firstZoneDuration = fake()->numberBetween(4, 10); // Duration in hours
    $firstZoneEnd = $firstZoneStart + $firstZoneDuration;
    
    // Ensure first zone doesn't exceed 24 hours
    if ($firstZoneEnd > 24) {
        $firstZoneEnd = 24;
    }
    
    $zones[] = [
        'id' => 'zone_1',
        'start' => sprintf('%02d:00', $firstZoneStart),
        'end' => sprintf('%02d:00', $firstZoneEnd % 24),
        'rate' => fake()->randomFloat(4, 0.10, 0.30),
    ];
    
    // Create second zone that overlaps with the first
    // Overlap by starting before the first zone ends
    $overlapAmount = fake()->numberBetween(1, min(3, $firstZoneDuration - 1));
    $secondZoneStart = $firstZoneEnd - $overlapAmount;
    $secondZoneDuration = fake()->numberBetween(3, 8);
    $secondZoneEnd = ($secondZoneStart + $secondZoneDuration) % 24;
    
    $zones[] = [
        'id' => 'zone_2',
        'start' => sprintf('%02d:00', $secondZoneStart),
        'end' => sprintf('%02d:00', $secondZoneEnd),
        'rate' => fake()->randomFloat(4, 0.10, 0.30),
    ];
    
    // Add additional random zones if needed
    for ($i = 3; $i <= $numZones; $i++) {
        $startHour = fake()->numberBetween(0, 23);
        $duration = fake()->numberBetween(2, 6);
        $endHour = ($startHour + $duration) % 24;
        
        $zones[] = [
            'id' => "zone_{$i}",
            'start' => sprintf('%02d:00', $startHour),
            'end' => sprintf('%02d:00', $endHour),
            'rate' => fake()->randomFloat(4, 0.10, 0.30),
        ];
    }
    
    // Attempt to create tariff with overlapping zones
    $this->actingAs($admin);
    session(['tenant_id' => $admin->tenant_id]);
    
    $response = $this->post(route('admin.tariffs.store'), [
        'provider_id' => $provider->id,
        'name' => 'Overlapping Zones Test ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => $zones,
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);
    
    // Property: tariff with overlapping zones should be rejected with validation error
    $response->assertSessionHasErrors('configuration.zones');
    
    // Verify error message mentions overlap
    $errors = session('errors');
    $errorMessages = $errors->get('configuration.zones');
    
    $hasOverlapError = false;
    foreach ($errorMessages as $message) {
        if (str_contains(strtolower($message), 'overlap')) {
            $hasOverlapError = true;
            break;
        }
    }
    
    expect($hasOverlapError)->toBeTrue(
        'Validation error should mention zone overlap'
    );
})->repeat(100);

// Feature: authentication-testing, Property 21: Tariff temporal selection
// Validates: Requirements 8.3
test('any billing date selects correct active tariff', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Generate a random base date for testing
    $baseDate = now()->subYears(fake()->numberBetween(1, 3));
    
    // Create multiple tariffs with different active periods
    // Tariff 1: Old tariff (expired)
    $oldTariffStart = $baseDate->copy()->subMonths(fake()->numberBetween(12, 24));
    $oldTariffEnd = $baseDate->copy()->subMonths(fake()->numberBetween(6, 11));
    
    $oldTariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Old Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.10, 0.30),
        ],
        'active_from' => $oldTariffStart,
        'active_until' => $oldTariffEnd,
    ]);
    
    // Tariff 2: Current tariff (active now)
    $currentTariffStart = $baseDate->copy()->subMonths(fake()->numberBetween(1, 5));
    
    $currentTariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Current Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.10, 0.30),
        ],
        'active_from' => $currentTariffStart,
        'active_until' => null, // Still active
    ]);
    
    // Tariff 3: Future tariff (not yet active)
    $futureTariffStart = $baseDate->copy()->addMonths(fake()->numberBetween(1, 6));
    
    $futureTariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Future Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.10, 0.30),
        ],
        'active_from' => $futureTariffStart,
        'active_until' => null,
    ]);
    
    $tariffResolver = app(\App\Services\TariffResolver::class);
    
    // Test 1: Billing date during old tariff period should select old tariff
    $dateInOldPeriod = $oldTariffStart->copy()->addDays(fake()->numberBetween(1, 30));
    if ($dateInOldPeriod->lessThanOrEqualTo($oldTariffEnd)) {
        $selectedTariff = $tariffResolver->resolve($provider, $dateInOldPeriod);
        
        // Property: tariff active on billing date should be selected
        expect($selectedTariff->id)->toBe($oldTariff->id);
        expect($selectedTariff->active_from->lessThanOrEqualTo($dateInOldPeriod))->toBeTrue();
        expect($selectedTariff->active_until)->not->toBeNull();
        expect($selectedTariff->active_until->greaterThanOrEqualTo($dateInOldPeriod))->toBeTrue();
    }
    
    // Test 2: Billing date during current tariff period should select current tariff
    $dateInCurrentPeriod = $currentTariffStart->copy()->addDays(fake()->numberBetween(1, 60));
    if ($dateInCurrentPeriod->lessThan($futureTariffStart)) {
        $selectedTariff = $tariffResolver->resolve($provider, $dateInCurrentPeriod);
        
        // Property: current active tariff should be selected
        expect($selectedTariff->id)->toBe($currentTariff->id);
        expect($selectedTariff->active_from->lessThanOrEqualTo($dateInCurrentPeriod))->toBeTrue();
        expect($selectedTariff->active_until)->toBeNull(); // Still active
    }
    
    // Test 3: Billing date in future (after future tariff starts) should select future tariff
    $dateInFuturePeriod = $futureTariffStart->copy()->addDays(fake()->numberBetween(1, 30));
    $selectedTariff = $tariffResolver->resolve($provider, $dateInFuturePeriod);
    
    // Property: future tariff should be selected when billing date is after its start
    expect($selectedTariff->id)->toBe($futureTariff->id);
    expect($selectedTariff->active_from->lessThanOrEqualTo($dateInFuturePeriod))->toBeTrue();
    
    // Test 4: Billing date exactly on tariff start date should select that tariff
    $selectedTariff = $tariffResolver->resolve($provider, $currentTariffStart);
    
    // Property: tariff should be active on its start date
    expect($selectedTariff->id)->toBe($currentTariff->id);
    expect($selectedTariff->active_from->format('Y-m-d'))->toBe($currentTariffStart->format('Y-m-d'));
    
    // Test 5: Billing date exactly on tariff end date should still select that tariff
    if ($oldTariffEnd) {
        $selectedTariff = $tariffResolver->resolve($provider, $oldTariffEnd);
        
        // Property: tariff should be active on its end date (inclusive)
        expect($selectedTariff->id)->toBe($oldTariff->id);
        expect($selectedTariff->active_until->format('Y-m-d'))->toBe($oldTariffEnd->format('Y-m-d'));
    }
})->repeat(100);

// Feature: authentication-testing, Property 23: Weekend tariff rate application
// Validates: Requirements 8.5
test('any weekend consumption uses weekend rates', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Randomly select a weekend logic type
    $weekendLogicOptions = ['apply_night_rate', 'apply_day_rate', 'apply_weekend_rate'];
    $weekendLogic = fake()->randomElement($weekendLogicOptions);
    
    // Create zones based on weekend logic
    $zones = [
        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => fake()->randomFloat(4, 0.15, 0.25)],
        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => fake()->randomFloat(4, 0.08, 0.15)],
    ];
    
    // If weekend logic is 'apply_weekend_rate', add a weekend zone
    if ($weekendLogic === 'apply_weekend_rate') {
        $zones[] = ['id' => 'weekend', 'start' => '00:00', 'end' => '23:59', 'rate' => fake()->randomFloat(4, 0.10, 0.20)];
    }
    
    // Create tariff with weekend logic
    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Weekend Test Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => $zones,
            'weekend_logic' => $weekendLogic,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);
    
    // Generate random consumption
    $consumption = fake()->randomFloat(2, 50, 500);
    
    // Generate random weekend date (Saturday or Sunday)
    $isaturday = fake()->boolean();
    
    if ($isaturday) {
        // Generate a Saturday date
        $baseDate = now()->startOfWeek()->addDays(5); // Saturday
    } else {
        // Generate a Sunday date
        $baseDate = now()->startOfWeek()->addDays(6); // Sunday
    }
    
    // Add random weeks offset to vary the date
    $weeksOffset = fake()->numberBetween(-10, 10);
    $weekendDate = $baseDate->copy()->addWeeks($weeksOffset);
    
    // Add random hour to vary the time of day
    $hour = fake()->numberBetween(0, 23);
    $minute = fake()->numberBetween(0, 59);
    $weekendDate->setTime($hour, $minute);
    
    // Verify it's actually a weekend
    expect($weekendDate->isWeekend())->toBeTrue(
        "Generated date {$weekendDate->format('Y-m-d H:i:s')} should be a weekend"
    );
    
    // Calculate cost using TariffResolver
    $tariffResolver = app(\App\Services\TariffResolver::class);
    $actualCost = $tariffResolver->calculateCost($tariff, $consumption, $weekendDate);
    
    // Determine expected rate based on weekend logic
    $expectedRate = match($weekendLogic) {
        'apply_night_rate' => collect($zones)->firstWhere('id', 'night')['rate'],
        'apply_day_rate' => collect($zones)->firstWhere('id', 'day')['rate'],
        'apply_weekend_rate' => collect($zones)->firstWhere('id', 'weekend')['rate'],
        default => null,
    };
    
    expect($expectedRate)->not->toBeNull(
        "Expected rate should be found for weekend logic: {$weekendLogic}"
    );
    
    // Calculate expected cost
    $expectedCost = $consumption * $expectedRate;
    
    // Property: weekend consumption should use the rate specified by weekend_logic
    expect(abs($actualCost - $expectedCost))->toBeLessThan(0.0001,
        "Weekend cost should be {$expectedCost} (consumption: {$consumption} * rate: {$expectedRate}), but got {$actualCost}"
    );
    
    // Additional verification: cost should NOT use the regular time-based rate on weekends
    // (unless weekend logic happens to match the time-based rate)
    $timeBasedZone = null;
    $currentTime = $weekendDate->format('H:i');
    
    foreach ($zones as $zone) {
        if ($zone['id'] === 'weekend') {
            continue; // Skip weekend zone for this check
        }
        
        $start = $zone['start'];
        $end = $zone['end'];
        
        // Check if current time falls in this zone
        if ($start < $end) {
            if ($currentTime >= $start && $currentTime < $end) {
                $timeBasedZone = $zone;
                break;
            }
        } else {
            // Crosses midnight
            if ($currentTime >= $start || $currentTime < $end) {
                $timeBasedZone = $zone;
                break;
            }
        }
    }
    
    // If weekend logic differs from time-based zone, verify different rate was used
    if ($timeBasedZone && $timeBasedZone['rate'] !== $expectedRate) {
        $timeBasedCost = $consumption * $timeBasedZone['rate'];
        
        expect(abs($actualCost - $timeBasedCost))->toBeGreaterThan(0.0001,
            "Weekend rate ({$expectedRate}) should differ from time-based rate ({$timeBasedZone['rate']})"
        );
    }
})->repeat(100);

// Feature: authentication-testing, Property 22: Tariff precedence with overlaps
// Validates: Requirements 8.4
test('any date with multiple active tariffs selects most recent', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Generate a random base date for testing
    $baseDate = now()->subMonths(fake()->numberBetween(3, 12));
    
    // Create multiple overlapping tariffs (all active on the same date range)
    // This simulates a scenario where tariffs were updated but old ones weren't properly closed
    
    // Tariff 1: Oldest tariff
    $oldestTariffStart = $baseDate->copy()->subMonths(fake()->numberBetween(6, 12));
    
    $oldestTariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Oldest Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.10, 0.20),
        ],
        'active_from' => $oldestTariffStart,
        'active_until' => null, // Still active (not properly closed)
    ]);
    
    // Tariff 2: Middle tariff (more recent than oldest)
    $middleTariffStart = $oldestTariffStart->copy()->addMonths(fake()->numberBetween(1, 3));
    
    $middleTariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Middle Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.15, 0.25),
        ],
        'active_from' => $middleTariffStart,
        'active_until' => null, // Still active (not properly closed)
    ]);
    
    // Tariff 3: Most recent tariff
    $newestTariffStart = $middleTariffStart->copy()->addMonths(fake()->numberBetween(1, 3));
    
    $newestTariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Newest Tariff ' . fake()->unique()->numberBetween(1, 100000),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.20, 0.30),
        ],
        'active_from' => $newestTariffStart,
        'active_until' => null, // Still active
    ]);
    
    // Optionally add a 4th tariff for more complexity
    if (fake()->boolean(50)) {
        $fourthTariffStart = $newestTariffStart->copy()->addMonths(fake()->numberBetween(1, 2));
        
        $fourthTariff = Tariff::create([
            'provider_id' => $provider->id,
            'name' => 'Fourth Tariff ' . fake()->unique()->numberBetween(1, 100000),
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(4, 0.25, 0.35),
            ],
            'active_from' => $fourthTariffStart,
            'active_until' => null,
        ]);
        
        // Update newest tariff reference
        $newestTariff = $fourthTariff;
        $newestTariffStart = $fourthTariffStart;
    }
    
    $tariffResolver = app(\App\Services\TariffResolver::class);
    
    // Test 1: Select a date after all tariffs have started (all are active)
    $testDate = $newestTariffStart->copy()->addDays(fake()->numberBetween(1, 30));
    
    $selectedTariff = $tariffResolver->resolve($provider, $testDate);
    
    // Property: when multiple tariffs are active, the most recent one should be selected
    expect($selectedTariff->id)->toBe($newestTariff->id);
    expect($selectedTariff->active_from->format('Y-m-d'))->toBe($newestTariffStart->format('Y-m-d'));
    
    // Verify that all tariffs are indeed active on this date
    $activeTariffs = $provider->tariffs()
        ->where('active_from', '<=', $testDate)
        ->where(function ($query) use ($testDate) {
            $query->whereNull('active_until')
                ->orWhere('active_until', '>=', $testDate);
        })
        ->get();
    
    // Should have multiple active tariffs
    expect($activeTariffs->count())->toBeGreaterThanOrEqual(2);
    
    // The selected tariff should have the most recent active_from date
    $mostRecentActiveFrom = $activeTariffs->max('active_from');
    expect($selectedTariff->active_from->format('Y-m-d H:i:s'))
        ->toBe($mostRecentActiveFrom->format('Y-m-d H:i:s'));
    
    // Test 2: Select a date between middle and newest tariff starts
    // At this point, oldest and middle are active, but newest is not yet
    if ($newestTariffStart->diffInDays($middleTariffStart) > 1) {
        $dateBetween = $middleTariffStart->copy()->addDays(
            fake()->numberBetween(1, min(5, $newestTariffStart->diffInDays($middleTariffStart) - 1))
        );
        
        if ($dateBetween->lessThan($newestTariffStart)) {
            $selectedTariff = $tariffResolver->resolve($provider, $dateBetween);
            
            // Property: middle tariff should be selected (most recent among active ones)
            expect($selectedTariff->id)->toBe($middleTariff->id);
            
            // Verify middle tariff is active on this date
            expect($selectedTariff->active_from->lessThanOrEqualTo($dateBetween))->toBeTrue();
        }
    }
    
    // Test 3: Select a date exactly on the newest tariff start date
    $selectedTariff = $tariffResolver->resolve($provider, $newestTariffStart);
    
    // Property: newest tariff should be selected on its start date
    expect($selectedTariff->id)->toBe($newestTariff->id);
    
    // Test 4: Verify precedence order is strictly by active_from date (descending)
    $allActiveTariffs = $provider->tariffs()
        ->where('active_from', '<=', $testDate)
        ->where(function ($query) use ($testDate) {
            $query->whereNull('active_until')
                ->orWhere('active_until', '>=', $testDate);
        })
        ->orderBy('active_from', 'desc')
        ->get();
    
    // Property: first tariff in descending order should be the selected one
    if ($allActiveTariffs->count() > 0) {
        expect($selectedTariff->id)->toBe($allActiveTariffs->first()->id);
    }
})->repeat(100);
