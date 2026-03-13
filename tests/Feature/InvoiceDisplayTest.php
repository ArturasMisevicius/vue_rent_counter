<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    // Set up tenant context
    session(['tenant_id' => 1]);
});

test('invoice summary component displays itemized breakdown', function () {
    $user = User::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => 150.00,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity - Day Rate',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.18,
        'total' => 18.00,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Water Supply',
        'quantity' => 50.00,
        'unit' => 'm³',
        'unit_price' => 0.97,
        'total' => 48.50,
    ]);

    $response = $this->actingAs($user)->get(route('invoices.show', $invoice));

    $response->assertStatus(200);
    $response->assertSee('Invoice #' . $invoice->id);
    $response->assertSee('Electricity - Day Rate');
    $response->assertSee('Water Supply');
    $response->assertSee('100.00');
    $response->assertSee('kWh');
    $response->assertSee('€0.1800');
    $response->assertSee('€18.00');
    $response->assertSee('€150.00');
});

test('invoice summary displays consumption amount and rate for each item', function () {
    $user = User::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Heating',
        'quantity' => 75.50,
        'unit' => 'kWh',
        'unit_price' => 0.12,
        'total' => 9.06,
        'meter_reading_snapshot' => [
            'previous_reading' => 1000.00,
            'current_reading' => 1075.50,
        ],
    ]);

    $response = $this->actingAs($user)->get(route('invoices.show', $invoice));

    $response->assertStatus(200);
    $response->assertSee('75.50');
    $response->assertSee('€0.1200');
    $response->assertSee('€9.06');
    $response->assertSee('Previous: 1,000.00');
    $response->assertSee('Current: 1,075.50');
});

test('tenant invoice view displays consumption history chronologically', function () {
    $user = User::factory()->create(['tenant_id' => 1, 'role' => 'tenant', 'email' => 'tenant@example.com']);
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'email' => 'tenant@example.com',
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);
    
    // Create readings in chronological order
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => '2024-01-05',
        'value' => 1000.00,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => '2024-01-15',
        'value' => 1050.00,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => '2024-01-25',
        'value' => 1100.00,
    ]);

    $response = $this->actingAs($user)->get(route('tenant.invoices.show', $invoice));

    $response->assertStatus(200);
    $response->assertSee('Consumption History');
    $response->assertSee('2024-01-05');
    $response->assertSee('2024-01-15');
    $response->assertSee('2024-01-25');
    
    // Verify chronological order in the HTML
    $content = $response->getContent();
    $pos1 = strpos($content, '2024-01-05');
    $pos2 = strpos($content, '2024-01-15');
    $pos3 = strpos($content, '2024-01-25');
    
    expect($pos1)->toBeLessThan($pos2);
    expect($pos2)->toBeLessThan($pos3);
});

test('tenant invoice index shows property filter for multi-property tenants', function () {
    $user = User::factory()->create(['tenant_id' => 1, 'role' => 'tenant', 'email' => 'tenant@example.com']);
    
    $property1 = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 1, Vilnius',
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Pilies g. 5, Vilnius',
    ]);
    
    // Create two tenants with the SAME email (multi-property tenant)
    $tenant1 = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property1->id,
        'email' => 'tenant@example.com',
    ]);
    
    $tenant2 = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property2->id,
        'email' => 'tenant@example.com', // Same email as tenant1
    ]);
    
    Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant1->id,
    ]);
    
    Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant2->id,
    ]);

    $response = $this->actingAs($user)->get(route('tenant.invoices.index'));

    $response->assertStatus(200);
    $response->assertSee('Filter by Property');
    $response->assertSee('Gedimino pr. 1, Vilnius');
    $response->assertSee('Pilies g. 5, Vilnius');
});

test('property filter correctly filters invoices by property', function () {
    $user = User::factory()->create(['tenant_id' => 1, 'role' => 'tenant', 'email' => 'tenant@example.com']);
    
    $property1 = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Property 1',
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Property 2',
    ]);
    
    $tenant1 = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property1->id,
        'email' => 'tenant@example.com',
    ]);
    
    $tenant2 = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property2->id,
        'email' => 'tenant2@example.com',
    ]);
    
    $invoice1 = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant1->id,
        'total_amount' => 100.00,
    ]);
    
    $invoice2 = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant2->id,
        'total_amount' => 200.00,
    ]);

    // Filter by property 1
    $response = $this->actingAs($user)->get(route('tenant.invoices.index', ['property_id' => $property1->id]));

    $response->assertStatus(200);
    $response->assertSee('€100.00');
    $response->assertDontSee('€200.00');
});

test('invoice status badge displays correctly', function () {
    $user = User::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $draftInvoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    $finalizedInvoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
    ]);
    
    $paidInvoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::PAID,
    ]);

    $response1 = $this->actingAs($user)->get(route('invoices.show', $draftInvoice));
    $response1->assertSee('Draft');
    $response1->assertSee('bg-yellow-100');
    
    $response2 = $this->actingAs($user)->get(route('invoices.show', $finalizedInvoice));
    $response2->assertSee('Finalized');
    $response2->assertSee('bg-blue-100');
    
    $response3 = $this->actingAs($user)->get(route('invoices.show', $paidInvoice));
    $response3->assertSee('Paid');
    $response3->assertSee('bg-green-100');
});
