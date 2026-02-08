<?php

use App\Enums\InvoiceStatus;
use App\Enums\DistributionMethod;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
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
        'supports_zones' => false,
    ]);

    $utilityService = UtilityService::create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity',
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'is_global_template' => false,
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'is_active' => true,
    ]);

    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 10],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subYear(),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter->update(['service_configuration_id' => $serviceConfiguration->id]);

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

    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startReading->reading_date, $endReading->reading_date);

    // Update end reading value
    $endReading->change_reason = 'Correction';
    $endReading->value = 250; // New consumption: 250 - 100 = 150
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem = $invoice->items()->first();

    // Assert invoice was recalculated
    expect($invoiceItem)->not->toBeNull();
    expect($invoiceItem->quantity)->toBe('150.00');
    expect($invoiceItem->total)->toBe('1500.00');
    expect($invoice->total_amount)->toBe('1500.00');
    expect($invoiceItem->meter_reading_snapshot['meters'][0]['end_value'])->toBe('250.00');
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
        'supports_zones' => false,
    ]);

    $utilityService = UtilityService::create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity',
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'is_global_template' => false,
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'is_active' => true,
    ]);

    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 10],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subYear(),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter->update(['service_configuration_id' => $serviceConfiguration->id]);

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

    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startReading->reading_date, $endReading->reading_date);
    $invoice->finalize();

    $invoiceItem = $invoice->items()->first();
    expect($invoiceItem)->not->toBeNull();

    $originalTotal = $invoice->total_amount;
    $originalItemTotal = $invoiceItem->total;

    // Update end reading value
    $endReading->change_reason = 'Correction';
    $endReading->value = 250;
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem = $invoice->items()->first();

    // Assert invoice was NOT recalculated (finalized invoices are immutable)
    expect($invoiceItem)->not->toBeNull();
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
        'supports_zones' => false,
    ]);

    $utilityService = UtilityService::create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity',
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'is_global_template' => false,
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'is_active' => true,
    ]);

    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 10],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subYear(),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter->update(['service_configuration_id' => $serviceConfiguration->id]);

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

    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startReading->reading_date, $endReading->reading_date);

    // Update start reading value
    $startReading->change_reason = 'Correction';
    $startReading->value = 50; // New consumption: 200 - 50 = 150
    $startReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem = $invoice->items()->first();

    // Assert invoice was recalculated
    expect($invoiceItem)->not->toBeNull();
    expect($invoiceItem->quantity)->toBe('150.00');
    expect($invoiceItem->total)->toBe('1500.00');
    expect($invoice->total_amount)->toBe('1500.00');
    expect($invoiceItem->meter_reading_snapshot['meters'][0]['start_value'])->toBe('50.00');
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
        'supports_zones' => false,
    ]);

    $utilityService = UtilityService::create([
        'tenant_id' => 1,
        'name' => 'Electricity',
        'slug' => 'electricity',
        'unit_of_measurement' => 'kWh',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'is_global_template' => false,
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'is_active' => true,
    ]);

    $serviceConfiguration = ServiceConfiguration::create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 10],
        'distribution_method' => DistributionMethod::EQUAL,
        'is_shared_service' => false,
        'effective_from' => now()->subYear(),
        'effective_until' => null,
        'is_active' => true,
    ]);

    $meter->update(['service_configuration_id' => $serviceConfiguration->id]);

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

    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $startReading->reading_date, $endReading->reading_date);

    // Update reading date but not value
    $endReading->reading_date = now()->addDay();
    $endReading->save();

    // Refresh models
    $invoice->refresh();
    $invoiceItem = $invoice->items()->first();

    // Assert invoice was NOT recalculated (value didn't change)
    expect($invoiceItem)->not->toBeNull();
    expect($invoiceItem->quantity)->toBe('100.00');
    expect($invoiceItem->total)->toBe('1000.00');
    expect($invoice->total_amount)->toBe('1000.00');
});
