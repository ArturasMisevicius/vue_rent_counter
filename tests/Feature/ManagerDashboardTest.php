<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

test('manager dashboard displays property statistics', function () {
    // Requirement 4.1: WHEN a Manager logs in THEN the System SHALL display the manager dashboard with property statistics
    
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create some properties
    Property::factory()->count(5)->create(['tenant_id' => $tenant->id]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Manager Dashboard');
    $response->assertSee('Total Properties');
    $response->assertSee('5'); // Should show count of 5 properties
});

test('manager dashboard shows counts of managed properties pending meter readings and draft invoices', function () {
    // Requirement 4.2: WHEN the manager dashboard loads THEN the System SHALL show counts of managed properties, pending meter readings, and draft invoices
    
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create properties
    $properties = Property::factory()->count(3)->create(['tenant_id' => $tenant->id]);
    
    // Create meters with and without readings for current month
    $meterWithReading = Meter::factory()->create([
        'property_id' => $properties[0]->id,
        'tenant_id' => $tenant->id,
    ]);
    
    $meterWithoutReading = Meter::factory()->create([
        'property_id' => $properties[1]->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Add reading for current month to first meter
    MeterReading::factory()->create([
        'meter_id' => $meterWithReading->id,
        'tenant_id' => $tenant->id,
        'reading_date' => Carbon::now()->startOfMonth(),
    ]);
    
    // Create draft invoices
    $renter = Tenant::factory()->create(['tenant_id' => $tenant->id]);
    Invoice::factory()->count(2)->create([
        'tenant_id' => $tenant->id,
        'tenant_renter_id' => $renter->id,
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Total Properties');
    $response->assertSee('Meters Pending Reading');
    $response->assertSee('Draft Invoices');
    $response->assertSee('2'); // Should show 2 draft invoices
});

test('manager dashboard shows properties requiring meter readings for current period', function () {
    // Requirement 4.4: WHEN the manager dashboard displays pending tasks THEN the System SHALL show properties requiring meter readings for the current period
    
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create property with meter that needs reading
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'address' => '123 Test Street',
    ]);
    
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);
    
    // Add reading from last month (so current month is pending)
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $tenant->id,
        'reading_date' => Carbon::now()->subMonth(),
    ]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Properties Requiring Meter Readings');
    $response->assertSee('123 Test Street');
    $response->assertSee('need reading for this month');
});

test('manager dashboard pending tasks navigate to appropriate data entry form', function () {
    // Requirement 4.5: WHEN a Manager clicks on a pending task THEN the System SHALL navigate to the appropriate data entry form
    
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create property with meter that needs reading
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    // Check that the "Enter Readings" button exists with correct link
    $response->assertSee('Enter Readings');
    $response->assertSee(route('manager.meter-readings.create', ['property_id' => $property->id]));
});

test('manager dashboard displays draft invoices summary', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Create property and renter
    $property = Property::factory()->create([
        'tenant_id' => $tenant->id,
        'address' => '456 Draft Invoice Street',
    ]);
    
    $renter = Tenant::factory()->create([
        'tenant_id' => $tenant->id,
        'property_id' => $property->id,
    ]);
    
    // Create draft invoice
    $invoice = Invoice::factory()->create([
        'tenant_id' => $tenant->id,
        'tenant_renter_id' => $renter->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 150.50,
        'billing_period_start' => Carbon::now()->startOfMonth(),
        'billing_period_end' => Carbon::now()->endOfMonth(),
    ]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Draft Invoices');
    $response->assertSee('456 Draft Invoice Street');
    $response->assertSee('150.50');
    $response->assertSee('Draft');
});

test('manager dashboard includes quick action links', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Quick Actions');
    $response->assertSee('Enter Meter Readings');
    $response->assertSee('Generate Invoice');
    $response->assertSee('View Properties');
    $response->assertSee('View Buildings');
    $response->assertSee('View Meters');
    $response->assertSee('View Reports');
});
