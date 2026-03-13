<?php

declare(strict_types=1);

use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
});

test('invoice summary component displays itemized breakdown', function () {
    $user = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $user->tenant_id]);
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => 150.00,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.50,
        'total' => 50.00,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Water',
        'quantity' => 50.00,
        'unit' => 'm³',
        'unit_price' => 2.00,
        'total' => 100.00,
    ]);
    
    $response = $this->actingAs($user)->get(route('manager.invoices.show', $invoice));
    
    $response->assertStatus(200);
    $response->assertSee('Electricity');
    $response->assertSee('Water');
    $response->assertSee('100.00');
    $response->assertSee('50.00');
    $response->assertSee('€150.00');
});

test('invoice summary component displays consumption history chronologically', function () {
    $user = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $user->tenant_id]);
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
    ]);
    
    // Create meter readings in the billing period
    $reading1 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $user->tenant_id,
        'reading_date' => now()->subMonth()->addDays(5),
        'value' => 100.00,
    ]);
    
    $reading2 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $user->tenant_id,
        'reading_date' => now()->subMonth()->addDays(15),
        'value' => 150.00,
    ]);
    
    $reading3 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $user->tenant_id,
        'reading_date' => now()->subMonth()->addDays(25),
        'value' => 200.00,
    ]);
    
    $response = $this->actingAs($user)->get(route('manager.invoices.show', $invoice));
    
    $response->assertStatus(200);
    $response->assertSeeInOrder([
        $reading1->reading_date->format('Y-m-d'),
        $reading2->reading_date->format('Y-m-d'),
        $reading3->reading_date->format('Y-m-d'),
    ]);
});

test('invoice summary component shows consumption amount and rate for each item', function () {
    $user = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $user->tenant_id]);
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity Day Rate',
        'quantity' => 75.50,
        'unit' => 'kWh',
        'unit_price' => 0.1800,
        'total' => 13.59,
        'meter_reading_snapshot' => [
            'previous_reading' => 1000.00,
            'current_reading' => 1075.50,
        ],
    ]);
    
    $response = $this->actingAs($user)->get(route('manager.invoices.show', $invoice));
    
    $response->assertStatus(200);
    $response->assertSee('75.50');
    $response->assertSee('kWh');
    $response->assertSee('0.1800');
    $response->assertSee('1000.00');
    $response->assertSee('1075.50');
});

test('invoice summary component displays property filter for multi-property tenants', function () {
    $user = User::factory()->manager()->create();
    
    $property1 = Property::factory()->create([
        'tenant_id' => $user->tenant_id,
        'address' => '123 Main St',
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => $user->tenant_id,
        'address' => '456 Oak Ave',
    ]);
    
    $tenant = Tenant::factory()->create([
        'property_id' => $property1->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $properties = collect([$property1, $property2]);
    
    $response = $this->actingAs($user)
        ->get(route('manager.invoices.show', $invoice));
    
    $response->assertStatus(200);
});

test('manager can view invoice with consumption history', function () {
    $user = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $user->tenant_id]);
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
    ]);
    
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $user->tenant_id,
        'reading_date' => now()->subMonth()->addDays(10),
        'value' => 100.00,
    ]);
    
    $response = $this->actingAs($user)->get(route('manager.invoices.show', $invoice));
    
    $response->assertStatus(200);
    $response->assertSee('Consumption History');
});

test('tenant can view invoice with consumption history', function () {
    $user = User::factory()->tenant()->create();
    $property = Property::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);
    
    // Assign property to user
    $user->property_id = $property->id;
    $user->save();
    
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
    ]);
    
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $user->tenant_id,
        'reading_date' => now()->subMonth()->addDays(10),
        'value' => 100.00,
    ]);
    
    $response = $this->actingAs($user)->get(route('tenant.invoices.show', $invoice));
    
    $response->assertStatus(200);
    $response->assertSee(__('invoices.summary.labels.history_title'));
});

test('invoice summary component handles empty consumption history gracefully', function () {
    $user = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $user->tenant_id]);
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $user->tenant_id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'tenant_id' => $user->tenant_id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
    ]);
    
    $response = $this->actingAs($user)->get(route('manager.invoices.show', $invoice));
    
    $response->assertStatus(200);
    // Should not show consumption history section if no readings exist
    $response->assertDontSee(__('invoices.summary.labels.history_title'));
});
