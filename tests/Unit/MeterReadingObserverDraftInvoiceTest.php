<?php

/**
 * MeterReadingObserver Draft Invoice Recalculation Tests
 *
 * Validates that the MeterReadingObserver correctly recalculates draft invoices
 * when meter readings are corrected, while protecting finalized invoices from
 * modification.
 *
 * @see \App\Observers\MeterReadingObserver
 * @see \App\Models\MeterReading
 * @see \App\Models\Invoice
 * @see \App\Models\InvoiceItem
 *
 * Requirements:
 * - Requirement 8.3: Draft invoice recalculation on reading correction
 * - Design Property 18: Automatic recalculation of affected draft invoices
 *
 * Test Coverage:
 * - Basic draft invoice recalculation
 * - Finalized invoice protection (immutability)
 * - Multiple affected invoices
 * - Multi-item invoice handling
 * - Orphan readings (no affected invoices)
 * - Start reading updates
 *
 * @package Tests\Unit
 * @group observers
 * @group billing
 * @group meter-readings
 * @group invoice-recalculation
 */

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Setup authenticated manager user for all tests.
 *
 * Creates a manager-role user with tenant_id = 1 and authenticates
 * them for the test context. This ensures all meter reading updates
 * have a valid user context for audit trail creation.
 */
beforeEach(function () {
    // Create a user to act as the authenticated user
    $this->user = User::factory()->create([
        'role' => 'manager',
        'tenant_id' => 1,
    ]);
    $this->actingAs($this->user);
});

/**
 * Test: Basic draft invoice recalculation on meter reading update.
 *
 * Validates that when a meter reading value is corrected, the system
 * automatically recalculates all affected draft invoices with the new
 * consumption values and updates the meter_reading_snapshot.
 *
 * Scenario:
 * - Draft invoice exists with consumption 100 kWh (1000→1100)
 * - End reading is corrected to 1150 kWh
 * - Expected: Invoice recalculated to 150 kWh, total updated
 *
 * @covers \App\Observers\MeterReadingObserver::updated
 * @covers \App\Observers\MeterReadingObserver::recalculateAffectedDraftInvoices
 * @covers \App\Observers\MeterReadingObserver::recalculateInvoice
 */
test('updating meter reading recalculates affected draft invoice', function () {
    // Create property and meter
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subMonth(),
        'tenant_id' => 1,
    ]);

    $endReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Create tenant
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    // Create draft invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 20.00, // 100 kWh * 0.20
        'tenant_id' => 1,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.2000,
        'total' => 20.00,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => '1000.00',
            'end_reading_id' => $endReading->id,
            'end_value' => '1100.00',
        ],
    ]);

    // Update the end reading value
    $endReading->change_reason = 'Correcting meter reading';
    $endReading->value = 1150.00; // New consumption: 150 kWh
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Verify invoice was recalculated
    expect($invoiceItem->quantity)->toBe('150.00'); // New consumption
    expect($invoiceItem->total)->toBe('30.00'); // 150 * 0.20
    expect($invoice->total_amount)->toBe('30.00');

    // Verify snapshot was updated
    $snapshot = $invoiceItem->meter_reading_snapshot;
    expect($snapshot['end_value'])->toBe('1150.00');
});

/**
 * Test: Finalized invoice protection from recalculation.
 *
 * Validates that finalized invoices are immutable and are NOT recalculated
 * when meter readings are corrected. This ensures billing integrity and
 * prevents retroactive changes to finalized financial records.
 *
 * Scenario:
 * - Finalized invoice exists with consumption 100 kWh
 * - End reading is corrected to 1150 kWh
 * - Expected: Invoice remains unchanged, no recalculation occurs
 *
 * @covers \App\Observers\MeterReadingObserver::recalculateAffectedDraftInvoices
 * @covers \App\Models\Invoice::scopeDraft
 */
test('updating meter reading does not recalculate finalized invoice', function () {
    // Create property and meter
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subMonth(),
        'tenant_id' => 1,
    ]);

    $endReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Create tenant
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    // Create finalized invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now()->subDay(),
        'total_amount' => 20.00,
        'tenant_id' => 1,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.2000,
        'total' => 20.00,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => '1000.00',
            'end_reading_id' => $endReading->id,
            'end_value' => '1100.00',
        ],
    ]);

    // Store original values
    $originalQuantity = $invoiceItem->quantity;
    $originalTotal = $invoiceItem->total;
    $originalInvoiceTotal = $invoice->total_amount;

    // Update the end reading value
    $endReading->change_reason = 'Correcting meter reading';
    $endReading->value = 1150.00;
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Verify invoice was NOT recalculated
    expect($invoiceItem->quantity)->toBe($originalQuantity);
    expect($invoiceItem->total)->toBe($originalTotal);
    expect($invoice->total_amount)->toBe($originalInvoiceTotal);
});

/**
 * Test: Multiple draft invoices recalculated from single reading change.
 *
 * Validates that when a meter reading is used in multiple invoices (as both
 * an end reading in one invoice and a start reading in another), updating
 * that reading correctly recalculates all affected draft invoices.
 *
 * Scenario:
 * - Reading sequence: 1000 → 1100 → 1200
 * - Invoice 1 uses 1000→1100 (100 kWh)
 * - Invoice 2 uses 1100→1200 (100 kWh)
 * - Middle reading (1100) updated to 1050
 * - Expected: Invoice 1 = 50 kWh, Invoice 2 = 150 kWh
 *
 * @covers \App\Observers\MeterReadingObserver::recalculateAffectedDraftInvoices
 * @covers \App\Observers\MeterReadingObserver::recalculateInvoice
 */
test('updating meter reading recalculates multiple affected draft invoices', function () {
    // Create property and meter
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    // Create meter readings
    $reading1 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subMonths(2),
        'tenant_id' => 1,
    ]);

    $reading2 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => now()->subMonth(),
        'tenant_id' => 1,
    ]);

    $reading3 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1200.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Create tenant
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    // Create first draft invoice (uses reading1 and reading2)
    $invoice1 = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 20.00,
        'tenant_id' => 1,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice1->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.2000,
        'total' => 20.00,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $reading1->id,
            'start_value' => '1000.00',
            'end_reading_id' => $reading2->id,
            'end_value' => '1100.00',
        ],
    ]);

    // Create second draft invoice (uses reading2 and reading3)
    $invoice2 = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 20.00,
        'tenant_id' => 1,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice2->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.2000,
        'total' => 20.00,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $reading2->id,
            'start_value' => '1100.00',
            'end_reading_id' => $reading3->id,
            'end_value' => '1200.00',
        ],
    ]);

    // Update reading2 (affects both invoices)
    $reading2->change_reason = 'Correcting meter reading';
    $reading2->value = 1050.00; // Changes consumption for both invoices
    $reading2->save();

    // Refresh models
    $invoice1->refresh();
    $invoice2->refresh();

    // Verify both invoices were recalculated
    // Invoice 1: 1050 - 1000 = 50 kWh * 0.20 = 10.00
    expect($invoice1->total_amount)->toBe('10.00');

    // Invoice 2: 1200 - 1050 = 150 kWh * 0.20 = 30.00
    expect($invoice2->total_amount)->toBe('30.00');
});

/**
 * Test: Multi-item invoice partial recalculation.
 *
 * Validates that when an invoice contains multiple utility types (e.g.,
 * electricity and water), only the affected items are recalculated when
 * a meter reading changes. Unaffected items remain unchanged.
 *
 * Scenario:
 * - Invoice has electricity (100 kWh × €0.20 = €20) + water (50 m³ × €2 = €100)
 * - Total: €120
 * - Electricity reading updated: 1100 → 1150 kWh
 * - Expected: Electricity = €30, Water = €100, Total = €130
 *
 * @covers \App\Observers\MeterReadingObserver::recalculateInvoice
 */
test('updating meter reading handles invoice with multiple items', function () {
    // Create property and meters
    $property = Property::factory()->create(['tenant_id' => 1]);
    
    $electricityMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    $waterMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
        'tenant_id' => 1,
    ]);

    // Create electricity readings
    $elecStart = MeterReading::factory()->create([
        'meter_id' => $electricityMeter->id,
        'value' => 1000.00,
        'reading_date' => now()->subMonth(),
        'tenant_id' => 1,
    ]);

    $elecEnd = MeterReading::factory()->create([
        'meter_id' => $electricityMeter->id,
        'value' => 1100.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Create water readings
    $waterStart = MeterReading::factory()->create([
        'meter_id' => $waterMeter->id,
        'value' => 500.00,
        'reading_date' => now()->subMonth(),
        'tenant_id' => 1,
    ]);

    $waterEnd = MeterReading::factory()->create([
        'meter_id' => $waterMeter->id,
        'value' => 550.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Create tenant
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    // Create draft invoice with multiple items
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 120.00, // 20 (elec) + 100 (water)
        'tenant_id' => 1,
    ]);

    // Electricity item
    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.2000,
        'total' => 20.00,
        'meter_reading_snapshot' => [
            'meter_id' => $electricityMeter->id,
            'start_reading_id' => $elecStart->id,
            'start_value' => '1000.00',
            'end_reading_id' => $elecEnd->id,
            'end_value' => '1100.00',
        ],
    ]);

    // Water item
    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Water',
        'quantity' => 50.00,
        'unit' => 'm³',
        'unit_price' => 2.0000,
        'total' => 100.00,
        'meter_reading_snapshot' => [
            'meter_id' => $waterMeter->id,
            'start_reading_id' => $waterStart->id,
            'start_value' => '500.00',
            'end_reading_id' => $waterEnd->id,
            'end_value' => '550.00',
        ],
    ]);

    // Update electricity reading
    $elecEnd->change_reason = 'Correcting meter reading';
    $elecEnd->value = 1150.00; // New consumption: 150 kWh
    $elecEnd->save();

    // Refresh invoice
    $invoice->refresh();

    // Verify invoice total was recalculated
    // New total: 30 (elec: 150 * 0.20) + 100 (water unchanged) = 130.00
    expect($invoice->total_amount)->toBe('130.00');
});

/**
 * Test: Orphan reading update (no affected invoices).
 *
 * Validates that updating a meter reading that is not referenced in any
 * invoice does not cause errors. The system should gracefully handle this
 * scenario without throwing exceptions.
 *
 * Scenario:
 * - Meter reading exists but not used in any invoice
 * - Reading value updated
 * - Expected: No errors, audit trail created, system stable
 *
 * @covers \App\Observers\MeterReadingObserver::recalculateAffectedDraftInvoices
 */
test('updating meter reading with no affected invoices does not cause errors', function () {
    // Create property and meter
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    // Create meter reading not used in any invoice
    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Update the reading
    $reading->change_reason = 'Correcting meter reading';
    $reading->value = 1050.00;
    
    // This should not throw any errors
    expect(fn() => $reading->save())->not->toThrow(Exception::class);
});

/**
 * Test: Start reading update triggers recalculation.
 *
 * Validates that updating the start reading (not just the end reading) of
 * a billing period correctly triggers invoice recalculation. This ensures
 * both start and end reading corrections are handled properly.
 *
 * Scenario:
 * - Invoice uses readings 1000→1100 (100 kWh)
 * - Start reading corrected to 950 kWh
 * - Expected: Consumption recalculated to 150 kWh (1100 - 950)
 *
 * @covers \App\Observers\MeterReadingObserver::recalculateAffectedDraftInvoices
 * @covers \App\Observers\MeterReadingObserver::recalculateInvoice
 */
test('updating start reading recalculates draft invoice correctly', function () {
    // Create property and meter
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subMonth(),
        'tenant_id' => 1,
    ]);

    $endReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    // Create tenant
    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    // Create draft invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 20.00,
        'tenant_id' => 1,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100.00,
        'unit' => 'kWh',
        'unit_price' => 0.2000,
        'total' => 20.00,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => '1000.00',
            'end_reading_id' => $endReading->id,
            'end_value' => '1100.00',
        ],
    ]);

    // Update the start reading value
    $startReading->change_reason = 'Correcting initial reading';
    $startReading->value = 950.00; // New consumption: 150 kWh (1100 - 950)
    $startReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Verify invoice was recalculated
    expect($invoiceItem->quantity)->toBe('150.00'); // New consumption
    expect($invoiceItem->total)->toBe('30.00'); // 150 * 0.20
    expect($invoice->total_amount)->toBe('30.00');

    // Verify snapshot was updated
    $snapshot = $invoiceItem->meter_reading_snapshot;
    expect($snapshot['start_value'])->toBe('950.00');
});
