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

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'manager',
        'tenant_id' => 1,
    ]);

    $this->actingAs($this->user);
});

test('updating meter reading recalculates affected draft invoice', function () {
    $billingService = app(BillingService::class);

    $periodStart = now()->subMonth()->startOfDay();
    $periodEnd = now()->startOfDay();

    $property = Property::factory()->create(['tenant_id' => 1]);

    $electricityService = UtilityService::factory()->create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity-' . uniqid(),
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);

    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $electricityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 0.2000],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(90),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
        'supports_zones' => false,
        'service_configuration_id' => $serviceConfiguration->id,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => $periodStart,
        'tenant_id' => 1,
    ]);

    $endReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => $periodEnd,
        'tenant_id' => 1,
    ]);

    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd)->load('items');

    expect($invoice->items)->toHaveCount(1);
    expect($invoice->total_amount)->toBe('20.00');

    $invoiceItem = $invoice->items->first();
    expect($invoiceItem)->not->toBeNull();
    expect($invoiceItem->quantity)->toBe('100.00');
    expect($invoiceItem->total)->toBe('20.00');

    $endReading->change_reason = 'Correcting meter reading';
    $endReading->value = 1150.00;
    $endReading->save();

    $invoice->refresh()->load('items');
    $invoiceItem = $invoice->items->first();

    expect($invoiceItem)->not->toBeNull();
    expect($invoiceItem->quantity)->toBe('150.00');
    expect($invoiceItem->total)->toBe('30.00');
    expect($invoice->total_amount)->toBe('30.00');

    $snapshot = $invoiceItem->meter_reading_snapshot;
    expect($snapshot['meters'][0]['end_value'])->toBe('1150.00');
});

test('updating meter reading does not recalculate finalized invoice', function () {
    $billingService = app(BillingService::class);

    $periodStart = now()->subMonth()->startOfDay();
    $periodEnd = now()->startOfDay();

    $property = Property::factory()->create(['tenant_id' => 1]);

    $electricityService = UtilityService::factory()->create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity-' . uniqid(),
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);

    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $electricityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 0.2000],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(90),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
        'supports_zones' => false,
        'service_configuration_id' => $serviceConfiguration->id,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => $periodStart,
        'tenant_id' => 1,
    ]);

    $endReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => $periodEnd,
        'tenant_id' => 1,
    ]);

    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);
    $invoice->update([
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now()->subDay(),
    ]);

    $invoice->load('items');
    expect($invoice->items)->toHaveCount(1);
    $invoiceItem = $invoice->items->first();

    $originalQuantity = $invoiceItem?->quantity;
    $originalTotal = $invoiceItem?->total;
    $originalInvoiceTotal = $invoice->total_amount;

    $endReading->change_reason = 'Correcting meter reading';
    $endReading->value = 1150.00;
    $endReading->save();

    $invoice->refresh()->load('items');
    $invoiceItem = $invoice->items->first();

    expect($invoiceItem?->quantity)->toBe($originalQuantity);
    expect($invoiceItem?->total)->toBe($originalTotal);
    expect($invoice->total_amount)->toBe($originalInvoiceTotal);
});

test('updating meter reading recalculates multiple affected draft invoices', function () {
    $billingService = app(BillingService::class);

    $property = Property::factory()->create(['tenant_id' => 1]);

    $electricityService = UtilityService::factory()->create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity-' . uniqid(),
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);

    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $electricityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 0.2000],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(90),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $date1 = now()->subMonths(2)->startOfDay();
    $date2 = now()->subMonth()->startOfDay();
    $date3 = now()->startOfDay();

    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
        'supports_zones' => false,
        'service_configuration_id' => $serviceConfiguration->id,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => $date1,
        'tenant_id' => 1,
    ]);

    $reading2 = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => $date2,
        'tenant_id' => 1,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1200.00,
        'reading_date' => $date3,
        'tenant_id' => 1,
    ]);

    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    $invoice1 = $billingService->generateInvoice($tenant, $date1, $date2);
    $invoice2 = $billingService->generateInvoice($tenant, $date2, $date3);

    $reading2->change_reason = 'Correcting meter reading';
    $reading2->value = 1050.00;
    $reading2->save();

    $invoice1->refresh();
    $invoice2->refresh();

    expect($invoice1->total_amount)->toBe('10.00');
    expect($invoice2->total_amount)->toBe('30.00');
});

test('updating meter reading handles invoice with multiple items', function () {
    $billingService = app(BillingService::class);

    $periodStart = now()->subMonth()->startOfDay();
    $periodEnd = now()->startOfDay();

    $property = Property::factory()->create(['tenant_id' => 1]);

    $electricityService = UtilityService::factory()->create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity-' . uniqid(),
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);

    $waterService = UtilityService::factory()->create([
        'tenant_id' => 1,
        'name' => 'Water',
        'slug' => 'water-' . uniqid(),
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);

    $electricityConfig = ServiceConfiguration::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $electricityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 0.2000],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(90),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $waterConfig = ServiceConfiguration::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $waterService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 2.0000],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(90),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $electricityMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
        'supports_zones' => false,
        'service_configuration_id' => $electricityConfig->id,
    ]);

    $waterMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
        'tenant_id' => 1,
        'supports_zones' => false,
        'service_configuration_id' => $waterConfig->id,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $electricityMeter->id,
        'value' => 1000.00,
        'reading_date' => $periodStart,
        'tenant_id' => 1,
    ]);

    $elecEnd = MeterReading::factory()->create([
        'meter_id' => $electricityMeter->id,
        'value' => 1100.00,
        'reading_date' => $periodEnd,
        'tenant_id' => 1,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $waterMeter->id,
        'value' => 500.00,
        'reading_date' => $periodStart,
        'tenant_id' => 1,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $waterMeter->id,
        'value' => 550.00,
        'reading_date' => $periodEnd,
        'tenant_id' => 1,
    ]);

    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd)->load('items');

    expect($invoice->items)->toHaveCount(2);
    expect($invoice->total_amount)->toBe('120.00');

    $electricityItem = $invoice->items->firstWhere('description', 'Electricity');
    $waterItem = $invoice->items->firstWhere('description', 'Water');

    expect($electricityItem)->not->toBeNull();
    expect($waterItem)->not->toBeNull();

    expect($electricityItem->quantity)->toBe('100.00');
    expect($electricityItem->total)->toBe('20.00');
    expect($waterItem->quantity)->toBe('50.00');
    expect($waterItem->total)->toBe('100.00');

    $elecEnd->change_reason = 'Correcting meter reading';
    $elecEnd->value = 1150.00;
    $elecEnd->save();

    $invoice->refresh()->load('items');

    $electricityItem = $invoice->items->firstWhere('description', 'Electricity');
    $waterItem = $invoice->items->firstWhere('description', 'Water');

    expect($electricityItem)->not->toBeNull();
    expect($waterItem)->not->toBeNull();

    expect($invoice->total_amount)->toBe('130.00');
    expect($electricityItem->quantity)->toBe('150.00');
    expect($electricityItem->total)->toBe('30.00');
    expect($waterItem->quantity)->toBe('50.00');
    expect($waterItem->total)->toBe('100.00');

    $snapshot = $electricityItem->meter_reading_snapshot;
    expect($snapshot['meters'][0]['end_value'])->toBe('1150.00');
});

test('updating meter reading with no affected invoices does not cause errors', function () {
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
    ]);

    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now(),
        'tenant_id' => 1,
    ]);

    $reading->change_reason = 'Correcting meter reading';
    $reading->value = 1050.00;

    expect(fn () => $reading->save())->not->toThrow(Exception::class);
});

test('updating start reading recalculates draft invoice correctly', function () {
    $billingService = app(BillingService::class);

    $periodStart = now()->subMonth()->startOfDay();
    $periodEnd = now()->startOfDay();

    $property = Property::factory()->create(['tenant_id' => 1]);

    $electricityService = UtilityService::factory()->create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity-' . uniqid(),
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
    ]);

    $serviceConfiguration = ServiceConfiguration::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $electricityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 0.2000],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subDays(90),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'tenant_id' => 1,
        'supports_zones' => false,
        'service_configuration_id' => $serviceConfiguration->id,
    ]);

    $startReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => $periodStart,
        'tenant_id' => 1,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1100.00,
        'reading_date' => $periodEnd,
        'tenant_id' => 1,
    ]);

    $tenant = Tenant::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd)->load('items');
    $invoiceItem = $invoice->items->firstWhere('description', 'Electricity');

    expect($invoiceItem)->not->toBeNull();
    expect($invoice->total_amount)->toBe('20.00');
    expect($invoiceItem->quantity)->toBe('100.00');
    expect($invoiceItem->total)->toBe('20.00');

    $startReading->change_reason = 'Correcting initial reading';
    $startReading->value = 950.00;
    $startReading->save();

    $invoice->refresh()->load('items');
    $invoiceItem = $invoice->items->firstWhere('description', 'Electricity');

    expect($invoiceItem)->not->toBeNull();
    expect($invoice->total_amount)->toBe('30.00');
    expect($invoiceItem->quantity)->toBe('150.00');
    expect($invoiceItem->total)->toBe('30.00');

    $snapshot = $invoiceItem->meter_reading_snapshot;
    expect($snapshot['meters'][0]['start_value'])->toBe('950.00');
});

