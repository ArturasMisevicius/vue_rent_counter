<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\ServiceType;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('updating meter reading recalculates affected draft invoices', function () {
    // Create test data
    $building = Building::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $building->id,
    ]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 100,
        'reading_date' => now()->subMonth(),
        'entered_by' => $this->user->id,
    ]);

    $endReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 200,
        'reading_date' => now(),
        'entered_by' => $this->user->id,
    ]);

    // Create draft invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 1000,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100, // consumption: 200 - 100
        'unit' => 'kWh',
        'unit_price' => 10,
        'total' => 1000,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => 100,
            'end_reading_id' => $endReading->id,
            'end_value' => 200,
        ],
    ]);

    // Update end reading value
    $endReading->change_reason = 'Correction';
    $endReading->value = 250; // New consumption: 250 - 100 = 150
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Assert invoice was recalculated
    expect($invoiceItem->quantity)->toBe('150.00');
    expect($invoiceItem->total)->toBe('1500.00');
    expect($invoice->total_amount)->toBe('1500.00');
    expect($invoiceItem->meter_reading_snapshot['end_value'])->toBe('250.00');
});

test('updating meter reading does not recalculate finalized invoices', function () {
    // Create test data
    $building = Building::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $building->id,
    ]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 100,
        'reading_date' => now()->subMonth(),
        'entered_by' => $this->user->id,
    ]);

    $endReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 200,
        'reading_date' => now(),
        'entered_by' => $this->user->id,
    ]);

    // Create finalized invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
        'total_amount' => 1000,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100,
        'unit' => 'kWh',
        'unit_price' => 10,
        'total' => 1000,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => 100,
            'end_reading_id' => $endReading->id,
            'end_value' => 200,
        ],
    ]);

    $originalTotal = $invoice->total_amount;
    $originalItemTotal = $invoiceItem->total;

    // Update end reading value
    $endReading->change_reason = 'Correction';
    $endReading->value = 250;
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Assert invoice was NOT recalculated (finalized invoices are immutable)
    expect($invoiceItem->quantity)->toBe('100.00');
    expect($invoiceItem->total)->toBe($originalItemTotal);
    expect($invoice->total_amount)->toBe($originalTotal);
});

test('updating start reading recalculates affected draft invoices', function () {
    // Create test data
    $building = Building::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $building->id,
    ]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 100,
        'reading_date' => now()->subMonth(),
        'entered_by' => $this->user->id,
    ]);

    $endReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 200,
        'reading_date' => now(),
        'entered_by' => $this->user->id,
    ]);

    // Create draft invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 1000,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100, // consumption: 200 - 100
        'unit' => 'kWh',
        'unit_price' => 10,
        'total' => 1000,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => 100,
            'end_reading_id' => $endReading->id,
            'end_value' => 200,
        ],
    ]);

    // Update start reading value
    $startReading->change_reason = 'Correction';
    $startReading->value = 50; // New consumption: 200 - 50 = 150
    $startReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Assert invoice was recalculated
    expect($invoiceItem->quantity)->toBe('150.00');
    expect($invoiceItem->total)->toBe('1500.00');
    expect($invoice->total_amount)->toBe('1500.00');
    expect($invoiceItem->meter_reading_snapshot['start_value'])->toBe('50.00');
});

test('updating meter reading without changing value does not trigger recalculation', function () {
    // Create test data
    $building = Building::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $building->id,
    ]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);

    // Create meter readings
    $startReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 100,
        'reading_date' => now()->subMonth(),
        'entered_by' => $this->user->id,
    ]);

    $endReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 200,
        'reading_date' => now(),
        'entered_by' => $this->user->id,
    ]);

    // Create draft invoice with item
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 1000,
    ]);

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Electricity',
        'quantity' => 100,
        'unit' => 'kWh',
        'unit_price' => 10,
        'total' => 1000,
        'meter_reading_snapshot' => [
            'meter_id' => $meter->id,
            'start_reading_id' => $startReading->id,
            'start_value' => 100,
            'end_reading_id' => $endReading->id,
            'end_value' => 200,
        ],
    ]);

    $originalUpdatedAt = $invoice->updated_at;

    // Update reading date but not value
    $endReading->reading_date = now()->addDay();
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem->refresh();

    // Assert invoice was NOT recalculated (value didn't change)
    expect($invoiceItem->quantity)->toBe('100.00');
    expect($invoiceItem->total)->toBe('1000.00');
    expect($invoice->total_amount)->toBe('1000.00');
});
