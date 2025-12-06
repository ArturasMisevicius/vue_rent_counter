<?php

declare(strict_types=1);

/**
 * BillingService Unit Tests
 * 
 * Tests the core billing service functionality including:
 * - Invoice generation with tariff snapshotting
 * - Multi-zone electricity meter handling
 * - Water billing with supply, sewage, and fixed fees
 * - Gyvatukas (circulation fee) calculations
 * - Invoice finalization and immutability
 * - Meter reading snapshot preservation
 * 
 * ## Data Model Notes
 * 
 * Invoice model has two tenant-related fields:
 * - `tenant_id`: Multi-tenancy scoping (via BelongsToTenant trait)
 * - `tenant_renter_id`: Foreign key to Tenant model (the actual renter)
 * 
 * The Invoice model auto-fills tenant_renter_id from tenant_id in the creating event
 * if not explicitly provided, but tests should create both for clarity.
 * 
 * ## Test Pattern
 * 
 * When creating Invoice models in tests:
 * 1. Create a Tenant model first
 * 2. Set both tenant_id (for scoping) and tenant_renter_id (FK to Tenant)
 * 3. This ensures proper relationship integrity and prevents FK constraint violations
 * 
 * Example:
 * ```php
 * $tenant = Tenant::factory()->create(['tenant_id' => $this->tenantId]);
 * $invoice = Invoice::factory()->create([
 *     'tenant_id' => $this->tenantId,
 *     'tenant_renter_id' => $tenant->id,
 * ]);
 * ```
 * 
 * @see \App\Models\Invoice
 * @see \App\Services\BillingService
 * @see \App\Traits\BelongsToTenant
 * 
 * @group billing
 * @group unit
 */

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
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
use App\Services\BillingService;
use App\Services\GyvatukasCalculator;
use App\Services\TariffResolver;
use Carbon\Carbon;

beforeEach(function () {
    $this->tenantId = 1;
    session(['tenant_id' => $this->tenantId]);
});

test('generateInvoice creates draft invoice with correct structure', function () {
    // Create test data
    $property = Property::factory()->create([
        'tenant_id' => $this->tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);

    $meter = Meter::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);

    $user = User::factory()->create(['tenant_id' => $this->tenantId]);

    // Create meter readings
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $startDate,
        'value' => 1000,
        'entered_by' => $user->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $endDate,
        'value' => 1100,
        'entered_by' => $user->id,
    ]);

    // Create provider and tariff
    $provider = Provider::factory()->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => $startDate->copy()->subMonth(),
        'active_until' => null,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startDate, $endDate);

    // Assertions
    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->status)->toBe(InvoiceStatus::DRAFT);
    expect($invoice->tenant_renter_id)->toBe($tenant->id);
    expect($invoice->billing_period_start->toDateString())->toBe($startDate->toDateString());
    expect($invoice->billing_period_end->toDateString())->toBe($endDate->toDateString());
    expect($invoice->total_amount)->toBeGreaterThan(0);
    expect($invoice->items)->toHaveCount(1);
});

test('generateInvoice calculates electricity consumption correctly', function () {
    // Create test data
    $property = Property::factory()->create([
        'tenant_id' => $this->tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);

    $meter = Meter::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);

    $user = User::factory()->create(['tenant_id' => $this->tenantId]);

    // Create meter readings with 100 kWh consumption
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $startDate,
        'value' => 1000,
        'entered_by' => $user->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $endDate,
        'value' => 1100,
        'entered_by' => $user->id,
    ]);

    // Create provider and tariff with 0.15 EUR/kWh rate
    $provider = Provider::factory()->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => $startDate->copy()->subMonth(),
        'active_until' => null,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startDate, $endDate);

    // Verify consumption and cost
    $item = $invoice->items->first();
    expect($item->quantity)->toBe('100.00'); // 1100 - 1000
    expect($item->unit)->toBe('kWh');
    expect($item->unit_price)->toBe('0.1500');
    expect($item->total)->toBe('15.00'); // 100 * 0.15
    expect($invoice->total_amount)->toBe('15.00');
});

test('generateInvoice handles water billing with supply, sewage, and fixed fee', function () {
    // Create test data
    $property = Property::factory()->create([
        'tenant_id' => $this->tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);

    $meter = Meter::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
        'supports_zones' => false,
    ]);

    $user = User::factory()->create(['tenant_id' => $this->tenantId]);

    // Create meter readings with 10 m³ consumption
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $startDate,
        'value' => 100,
        'entered_by' => $user->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $endDate,
        'value' => 110,
        'entered_by' => $user->id,
    ]);

    // Create provider and tariff
    $provider = Provider::factory()->create([
        'service_type' => ServiceType::WATER,
    ]);

    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'configuration' => [
            'type' => 'flat',
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
            'fixed_fee' => 0.85,
            'currency' => 'EUR',
        ],
        'active_from' => $startDate->copy()->subMonth(),
        'active_until' => null,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startDate, $endDate);

    // Verify water billing calculation
    // Expected: (10 × 0.97) + (10 × 1.23) = 22.00 for consumption
    // Plus 0.85 fixed fee = 22.85 total
    expect($invoice->items)->toHaveCount(2); // Consumption + fixed fee
    
    $consumptionItem = $invoice->items->first();
    expect($consumptionItem->quantity)->toBe('10.00');
    expect($consumptionItem->unit)->toBe('m³');
    expect($consumptionItem->unit_price)->toBe('2.2000'); // 0.97 + 1.23
    expect($consumptionItem->total)->toBe('22.00');
    
    $fixedFeeItem = $invoice->items->last();
    expect($fixedFeeItem->quantity)->toBe('1.00');
    expect($fixedFeeItem->unit)->toBe('month');
    // unit_price is stored as decimal:4, but when it's a round number like 0.85, 
    // Laravel may return it as '0.85' instead of '0.8500'
    expect((float) $fixedFeeItem->unit_price)->toBe(0.85);
    expect($fixedFeeItem->total)->toBe('0.85');
    
    expect($invoice->total_amount)->toBe('22.85');
});

test('generateInvoice snapshots meter readings and tariff configuration', function () {
    // Create test data
    $property = Property::factory()->create([
        'tenant_id' => $this->tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);

    $meter = Meter::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);

    $user = User::factory()->create(['tenant_id' => $this->tenantId]);

    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    $startReading = MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $startDate,
        'value' => 1000,
        'entered_by' => $user->id,
    ]);

    $endReading = MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $endDate,
        'value' => 1100,
        'entered_by' => $user->id,
    ]);

    $provider = Provider::factory()->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => $startDate->copy()->subMonth(),
        'active_until' => null,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startDate, $endDate);

    // Verify snapshot data
    $item = $invoice->items->first();
    $snapshot = $item->meter_reading_snapshot;

    expect($snapshot)->toBeArray();
    expect($snapshot['meter_id'])->toBe($meter->id);
    expect($snapshot['meter_serial'])->toBe($meter->serial_number);
    expect($snapshot['start_reading_id'])->toBe($startReading->id);
    expect($snapshot['start_value'])->toBe('1000.00');
    expect($snapshot['end_reading_id'])->toBe($endReading->id);
    expect($snapshot['end_value'])->toBe('1100.00');
    expect($snapshot['tariff_id'])->toBe($tariff->id);
    expect($snapshot['tariff_configuration'])->toBe($tariff->configuration);
});

/**
 * Test invoice finalization sets status and timestamp correctly.
 * 
 * This test verifies that:
 * - Invoice status changes from DRAFT to FINALIZED
 * - finalized_at timestamp is set to current time
 * - The finalization process completes without errors
 * 
 * ## Test Data Setup Pattern
 * 
 * Note the proper order of model creation:
 * 1. Create Tenant model first (required for FK relationship)
 * 2. Create Invoice with both tenant_id and tenant_renter_id
 * 3. Create InvoiceItem linked to the invoice
 * 
 * This pattern ensures:
 * - Foreign key constraints are satisfied
 * - Multi-tenancy scoping works correctly
 * - Relationship integrity is maintained
 * 
 * @see \App\Services\BillingService::finalizeInvoice()
 * @see \App\Models\Invoice::finalize()
 */
test('finalizeInvoice sets status and timestamp', function () {
    // Create tenant first - required for tenant_renter_id FK relationship
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
    ]);

    // Create invoice with both tenant_id (scoping) and tenant_renter_id (FK)
    $invoice = Invoice::factory()->create([
        'tenant_id' => $this->tenantId,
        'tenant_renter_id' => $tenant->id,
        'status' => InvoiceStatus::DRAFT,
        'finalized_at' => null,
        'total_amount' => 10.00,
    ]);

    // Create invoice item - no tenant_id needed (inherited from invoice)
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Item',
        'quantity' => '1.00',
        'unit' => 'unit',
        'unit_price' => '10.00',
        'total' => '10.00',
        'meter_reading_snapshot' => [],
    ]);

    $billingService = app(BillingService::class);
    $billingService->finalizeInvoice($invoice);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED);
    expect($invoice->finalized_at)->not->toBeNull();
});

test('finalizeInvoice throws exception if already finalized', function () {
    $invoice = Invoice::factory()->create([
        'tenant_id' => $this->tenantId,
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);

    $billingService = app(BillingService::class);
    
    expect(fn() => $billingService->finalizeInvoice($invoice))
        ->toThrow(\App\Exceptions\InvoiceAlreadyFinalizedException::class);
});

test('generateInvoice handles multi-zone electricity meters', function () {
    // Create test data
    $property = Property::factory()->create([
        'tenant_id' => $this->tenantId,
        'type' => PropertyType::APARTMENT,
    ]);
    
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);

    $meter = Meter::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);

    $user = User::factory()->create(['tenant_id' => $this->tenantId]);

    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    // Day zone readings
    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $startDate,
        'value' => 1000,
        'zone' => 'day',
        'entered_by' => $user->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $endDate,
        'value' => 1050,
        'zone' => 'day',
        'entered_by' => $user->id,
    ]);

    // Night zone readings
    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $startDate,
        'value' => 500,
        'zone' => 'night',
        'entered_by' => $user->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $endDate,
        'value' => 550,
        'zone' => 'night',
        'entered_by' => $user->id,
    ]);

    // Create provider and tariff
    $provider = Provider::factory()->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'configuration' => [
            'type' => 'time_of_use',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'currency' => 'EUR',
        ],
        'active_from' => $startDate->copy()->subMonth(),
        'active_until' => null,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startDate, $endDate);

    // Should have 2 items (day and night)
    expect($invoice->items)->toHaveCount(2);
    
    $dayItem = $invoice->items->where('meter_reading_snapshot.zone', 'day')->first();
    $nightItem = $invoice->items->where('meter_reading_snapshot.zone', 'night')->first();
    
    expect($dayItem)->not->toBeNull();
    expect($nightItem)->not->toBeNull();
    expect($dayItem->quantity)->toBe('50.00');
    expect($nightItem->quantity)->toBe('50.00');
});
