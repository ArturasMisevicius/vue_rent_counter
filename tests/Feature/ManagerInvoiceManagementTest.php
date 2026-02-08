<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;

beforeEach(function () {
    // Set up tenant context
    session(['tenant_id' => 1]);
});

test('manager can view invoice index with status filtering', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.index'));

    $response->assertStatus(200);
    $response->assertSee('Invoices');
    $response->assertSee('Filter by Status');
    $response->assertSee('Draft');
    $response->assertSee('Finalized');
});

test('manager can filter invoices by status', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $draftInvoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 100.00,
    ]);
    
    $finalizedInvoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => 200.00,
    ]);

    // Filter by draft status
    $response = $this->actingAs($manager)->get(route('manager.invoices.index', ['status' => 'draft']));

    $response->assertStatus(200);
    $response->assertSee('€100.00');
    $response->assertDontSee('€200.00');
});

test('manager can view invoice details with line items', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
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

    $response = $this->actingAs($manager)->get(route('manager.invoices.show', $invoice));

    $response->assertStatus(200);
    $response->assertSee('Invoice #' . $invoice->id);
    $response->assertSee('Electricity - Day Rate');
    $response->assertSee('100.00');
    $response->assertSee('€0.1800');
    $response->assertSee('€150.00');
});

test('manager can see edit button for draft invoices', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
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

    $response = $this->actingAs($manager)->get(route('manager.invoices.show', $draftInvoice));

    $response->assertStatus(200);
    $response->assertSee('Edit Invoice');
    $response->assertSee('Finalize Invoice');
    $response->assertSee('Draft Invoice');
});

test('manager cannot see edit button for finalized invoices', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $finalizedInvoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.show', $finalizedInvoice));

    $response->assertStatus(200);
    $response->assertDontSee('Edit Invoice');
    $response->assertDontSee('Finalize Invoice');
});

test('manager can access invoice creation form', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.create'));

    $response->assertStatus(200);
    $response->assertSee('Generate Invoice');
    $response->assertSee('Tenant');
    $response->assertSee('Billing Period Start');
    $response->assertSee('Billing Period End');
    $response->assertSee($tenant->name);
});

test('manager can finalize a draft invoice', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 100.00,
    ]);
    
    // Add invoice items
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 100.00,
        'unit_price' => 1.00,
        'total' => 100.00,
    ]);

    $response = $this->actingAs($manager)->post(route('manager.invoices.finalize', $invoice));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Invoice finalized successfully. It is now immutable.');
    
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED);
    expect($invoice->finalized_at)->not->toBeNull();
});

test('manager cannot finalize an already finalized invoice', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => 100.00,
    ]);
    
    // Add invoice items
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 100.00,
        'unit_price' => 1.00,
        'total' => 100.00,
    ]);

    $response = $this->actingAs($manager)->post(route('manager.invoices.finalize', $invoice));

    // Should fail validation
    $response->assertSessionHasErrors();
});

test('manager can access edit form for draft invoice', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.edit', $invoice));

    $response->assertStatus(200);
    $response->assertSee('Edit Invoice #' . $invoice->id);
    $response->assertSee('Tenant');
    $response->assertSee('Billing Period Start');
    $response->assertSee('Billing Period End');
});

test('manager cannot access edit form for finalized invoice', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.edit', $invoice));

    // Policy denies access, so we get a 403 or redirect with error
    expect($response->status())->toBeIn([302, 403]);
});

test('invoice shows billing period information', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.show', $invoice));

    $response->assertStatus(200);
    $response->assertSee('Billing Period');
    $response->assertSee('Jan 01, 2024');
    $response->assertSee('Jan 31, 2024');
});

test('invoice shows tenant information', function () {
    $manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 1, Vilnius',
    ]);
    
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
    ]);

    $response = $this->actingAs($manager)->get(route('manager.invoices.show', $invoice));

    $response->assertStatus(200);
    $response->assertSee('Tenant Information');
    $response->assertSee('John Doe');
    $response->assertSee('john@example.com');
    $response->assertSee('Gedimino pr. 1, Vilnius');
});

