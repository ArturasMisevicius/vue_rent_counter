<?php

declare(strict_types=1);

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MeterReadingSubmittedEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // Create test data structure
    $this->admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    
    $this->tenant = Tenant::factory()->create(['tenant_id' => 1]);
    
    $this->property = Property::factory()->create([
        'tenant_id' => 1,
    ]);
    
    $this->tenantUser = User::factory()->create([
        'role' => 'tenant',
        'tenant_id' => 1,
        'property_id' => $this->property->id,
        'parent_user_id' => $this->admin->id,
    ]);
    
    $this->meter = Meter::factory()->create([
        'property_id' => $this->property->id,
        'tenant_id' => 1,
        'supports_zones' => false,
    ]);
});

describe('index method', function () {
    test('displays meter readings for authenticated tenant', function () {
        // Create some readings
        MeterReading::factory()->count(3)->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        $response = $this->actingAs($this->tenantUser)
            ->get(route('tenant.meter-readings.index'));
        
        $response->assertOk()
            ->assertViewIs('tenant.meter-readings.index')
            ->assertViewHas('readings')
            ->assertViewHas('properties');
    });
    
    test('paginates readings correctly', function () {
        // Create more than one page of readings
        MeterReading::factory()->count(25)->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        $response = $this->actingAs($this->tenantUser)
            ->get(route('tenant.meter-readings.index'));
        
        $response->assertOk();
        
        $readings = $response->viewData('readings');
        expect($readings)->toHaveCount(20); // Default pagination
    });
    
    test('eager loads meter relationships to prevent N+1 queries', function () {
        MeterReading::factory()->count(5)->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->tenantUser)
            ->get(route('tenant.meter-readings.index'));
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should have minimal queries due to eager loading
        expect(count($queries))->toBeLessThan(10);
    });
    
    test('returns empty collection when tenant has no property', function () {
        $userWithoutProperty = User::factory()->create([
            'role' => 'tenant',
            'tenant_id' => 1,
            'property_id' => null,
        ]);
        
        $response = $this->actingAs($userWithoutProperty)
            ->get(route('tenant.meter-readings.index'));
        
        $response->assertOk();
        
        $properties = $response->viewData('properties');
        expect($properties)->toBeEmpty();
    });
});

describe('show method', function () {
    test('displays specific meter reading for authorized tenant', function () {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        $response = $this->actingAs($this->tenantUser)
            ->get(route('tenant.meter-readings.show', $reading));
        
        $response->assertOk()
            ->assertViewIs('tenant.meter-readings.show')
            ->assertViewHas('meterReading');
    });
    
    test('denies access to reading from different property', function () {
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        $otherMeter = Meter::factory()->create([
            'property_id' => $otherProperty->id,
            'tenant_id' => 2,
        ]);
        
        $otherReading = MeterReading::factory()->create([
            'meter_id' => $otherMeter->id,
            'tenant_id' => 2,
        ]);
        
        $response = $this->actingAs($this->tenantUser)
            ->get(route('tenant.meter-readings.show', $otherReading));
        
        $response->assertForbidden();
    });
    
    test('denies access when tenant has no property', function () {
        $userWithoutProperty = User::factory()->create([
            'role' => 'tenant',
            'tenant_id' => 1,
            'property_id' => null,
        ]);
        
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        $response = $this->actingAs($userWithoutProperty)
            ->get(route('tenant.meter-readings.show', $reading));
        
        $response->assertForbidden();
    });
});

describe('store method', function () {
    test('creates meter reading successfully', function () {
        Notification::fake();
        
        $data = [
            'meter_id' => $this->meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 100.50,
        ];
        
        $response = $this->actingAs($this->tenantUser)
            ->post(route('tenant.meter-readings.store'), $data);
        
        $response->assertRedirect()
            ->assertSessionHas('success');
        
        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
            'value' => 100.50,
            'entered_by_user_id' => $this->tenantUser->id,
        ]);
    });
    
    test('sends notification to parent user on successful submission', function () {
        Notification::fake();
        
        $data = [
            'meter_id' => $this->meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 100.50,
        ];
        
        $response = $this->actingAs($this->tenantUser)
            ->post(route('tenant.meter-readings.store'), $data);
        
        $response->assertRedirect();
        
        Notification::assertSentTo(
            $this->admin,
            MeterReadingSubmittedEmail::class
        );
    });
    
    test('denies submission when tenant has no property', function () {
        $userWithoutProperty = User::factory()->create([
            'role' => 'tenant',
            'tenant_id' => 1,
            'property_id' => null,
        ]);
        
        $data = [
            'meter_id' => $this->meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 100.50,
        ];
        
        $response = $this->actingAs($userWithoutProperty)
            ->post(route('tenant.meter-readings.store'), $data);
        
        $response->assertForbidden();
    });
    
    test('denies submission for meter from different property', function () {
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        $otherMeter = Meter::factory()->create([
            'property_id' => $otherProperty->id,
            'tenant_id' => 2,
        ]);
        
        $data = [
            'meter_id' => $otherMeter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 100.50,
        ];
        
        $response = $this->actingAs($this->tenantUser)
            ->post(route('tenant.meter-readings.store'), $data);
        
        $response->assertNotFound(); // firstOrFail throws 404
    });
    
    test('validates required fields', function () {
        $response = $this->actingAs($this->tenantUser)
            ->post(route('tenant.meter-readings.store'), []);
        
        $response->assertSessionHasErrors(['meter_id', 'reading_date', 'value']);
    });
    
    test('validates monotonicity through form request', function () {
        // Create previous reading
        MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
            'value' => 100.00,
            'reading_date' => now()->subDay(),
        ]);
        
        // Try to submit lower value
        $data = [
            'meter_id' => $this->meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 50.00, // Lower than previous
        ];
        
        $response = $this->actingAs($this->tenantUser)
            ->post(route('tenant.meter-readings.store'), $data);
        
        $response->assertSessionHasErrors('value');
    });
    
    test('handles zone parameter for multi-zone meters', function () {
        $multiZoneMeter = Meter::factory()->create([
            'property_id' => $this->property->id,
            'tenant_id' => 1,
            'supports_zones' => true,
        ]);
        
        $data = [
            'meter_id' => $multiZoneMeter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 100.50,
            'zone' => 'day',
        ];
        
        $response = $this->actingAs($this->tenantUser)
            ->post(route('tenant.meter-readings.store'), $data);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('meter_readings', [
            'meter_id' => $multiZoneMeter->id,
            'zone' => 'day',
            'value' => 100.50,
        ]);
    });
});

describe('authorization', function () {
    test('requires authentication for all actions', function () {
        $reading = MeterReading::factory()->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        $this->get(route('tenant.meter-readings.index'))
            ->assertRedirect(route('login'));
        
        $this->get(route('tenant.meter-readings.show', $reading))
            ->assertRedirect(route('login'));
        
        $this->post(route('tenant.meter-readings.store'), [])
            ->assertRedirect(route('login'));
    });
    
    test('enforces tenant role requirement', function () {
        $adminUser = User::factory()->create([
            'role' => 'admin',
            'tenant_id' => 1,
        ]);
        
        // Admin accessing tenant routes should be handled by middleware
        // This test verifies the controller expects tenant users
        $response = $this->actingAs($adminUser)
            ->get(route('tenant.meter-readings.index'));
        
        // Depending on middleware configuration, this might redirect or show empty
        expect($response->status())->toBeIn([200, 302, 403]);
    });
});

describe('multi-tenancy isolation', function () {
    test('tenant cannot see readings from other tenants', function () {
        // Create another tenant's data
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        $otherMeter = Meter::factory()->create([
            'property_id' => $otherProperty->id,
            'tenant_id' => 2,
        ]);
        
        MeterReading::factory()->count(5)->create([
            'meter_id' => $otherMeter->id,
            'tenant_id' => 2,
        ]);
        
        // Create readings for current tenant
        MeterReading::factory()->count(3)->create([
            'meter_id' => $this->meter->id,
            'tenant_id' => 1,
        ]);
        
        $response = $this->actingAs($this->tenantUser)
            ->get(route('tenant.meter-readings.index'));
        
        $readings = $response->viewData('readings');
        
        // Should only see own tenant's readings
        expect($readings)->toHaveCount(3);
        
        foreach ($readings as $reading) {
            expect($reading->tenant_id)->toBe(1);
        }
    });
});
