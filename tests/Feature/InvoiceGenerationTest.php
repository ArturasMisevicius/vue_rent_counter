<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
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
use Carbon\Carbon;

/**
 * Invoice Generation Tests
 * 
 * Tests that invoices are correctly calculated from meter readings and tariffs,
 * that invoice items are created for each utility type,
 * that tariff rates are snapshotted in invoice_items,
 * that finalized invoices cannot be modified,
 * and that finalized invoices are not recalculated when tariffs change.
 * 
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5
 */

test('invoice is calculated from meter readings and tariffs', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Create tenant (renter)
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'John Doe',
        'email' => 'tenant@test.com',
        'property_id' => $property->id,
        'lease_start' => now()->subMonths(6),
        'lease_end' => now()->addMonths(6),
    ]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create electricity meter
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'EL-000001',
        'type' => MeterType::ELECTRICITY,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create provider and tariff
    $provider = Provider::create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Standard Rate',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);

    // Create meter readings
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();

    $this->attachConsumptionServiceToMeter(
        meter: $meter,
        serviceName: 'Electricity',
        unitOfMeasurement: 'kWh',
        unitRate: 0.15,
        bridgeType: ServiceType::ELECTRICITY,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $provider->id,
        tariffId: $tariff->id,
    );

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => 1200.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

    // Assert invoice was created
    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->status)->toBe(InvoiceStatus::DRAFT);
    
    // Assert invoice total is calculated from consumption (200 kWh * 0.15 EUR/kWh = 30 EUR)
    expect($invoice->total_amount)->toBeGreaterThan(0);
    
    // Assert invoice has items
    expect($invoice->items)->toHaveCount(1);
    
    // Assert item has correct consumption
    $item = $invoice->items->first();
    expect((float)$item->quantity)->toBe(200.0);
});

test('invoice items are created for each utility type', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Create tenant (renter)
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'John Doe',
        'email' => 'tenant@test.com',
        'property_id' => $property->id,
        'lease_start' => now()->subMonths(6),
        'lease_end' => now()->addMonths(6),
    ]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create providers and tariffs
    $electricityProvider = Provider::create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $electricityTariff = Tariff::create([
        'provider_id' => $electricityProvider->id,
        'name' => 'Electricity Standard',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);

    $waterProvider = Provider::create([
        'name' => 'Vilniaus Vandenys',
        'service_type' => ServiceType::WATER,
    ]);

    $waterTariff = Tariff::create([
        'provider_id' => $waterProvider->id,
        'name' => 'Water Standard',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'supply_rate' => 0.97,
            'sewage_rate' => 1.23,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);

    // Create meters
    $electricityMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'EL-000001',
        'type' => MeterType::ELECTRICITY,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    $waterColdMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'WC-000001',
        'type' => MeterType::WATER_COLD,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    $waterHotMeter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'WH-000001',
        'type' => MeterType::WATER_HOT,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create meter readings
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();

    $waterUnitRate = (float) ($waterTariff->configuration['supply_rate'] ?? 0.0)
        + (float) ($waterTariff->configuration['sewage_rate'] ?? 0.0);

    $this->attachConsumptionServiceToMeter(
        meter: $electricityMeter,
        serviceName: 'Electricity',
        unitOfMeasurement: 'kWh',
        unitRate: 0.15,
        bridgeType: ServiceType::ELECTRICITY,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $electricityProvider->id,
        tariffId: $electricityTariff->id,
    );

    $this->attachConsumptionServiceToMeter(
        meter: $waterColdMeter,
        serviceName: 'Cold Water',
        unitOfMeasurement: 'm3',
        unitRate: $waterUnitRate,
        bridgeType: ServiceType::WATER,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $waterProvider->id,
        tariffId: $waterTariff->id,
    );

    $this->attachConsumptionServiceToMeter(
        meter: $waterHotMeter,
        serviceName: 'Hot Water',
        unitOfMeasurement: 'm3',
        unitRate: $waterUnitRate,
        bridgeType: ServiceType::WATER,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $waterProvider->id,
        tariffId: $waterTariff->id,
    );

    // Electricity readings
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $electricityMeter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $electricityMeter->id,
        'reading_date' => $periodEnd,
        'value' => 1200.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Water cold readings
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $waterColdMeter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 50.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $waterColdMeter->id,
        'reading_date' => $periodEnd,
        'value' => 55.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Water hot readings
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $waterHotMeter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 30.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $waterHotMeter->id,
        'reading_date' => $periodEnd,
        'value' => 33.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

    // Assert invoice has items for each utility type
    expect($invoice->items)->toHaveCount(3);
    
    // Assert we have items for electricity and water
    $itemDescriptions = $invoice->items->pluck('description')->toArray();
    expect($itemDescriptions)->toContain('Electricity');
    expect($itemDescriptions)->toContain('Cold Water');
    expect($itemDescriptions)->toContain('Hot Water');
});

test('tariff rates are snapshotted in invoice_items', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Create tenant (renter)
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'John Doe',
        'email' => 'tenant@test.com',
        'property_id' => $property->id,
        'lease_start' => now()->subMonths(6),
        'lease_end' => now()->addMonths(6),
    ]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create electricity meter
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'EL-000001',
        'type' => MeterType::ELECTRICITY,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create provider and tariff with specific rate
    $provider = Provider::create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $originalRate = 0.15;
    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Standard Rate',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => $originalRate,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);

    // Create meter readings
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();

    $this->attachConsumptionServiceToMeter(
        meter: $meter,
        serviceName: 'Electricity',
        unitOfMeasurement: 'kWh',
        unitRate: $originalRate,
        bridgeType: ServiceType::ELECTRICITY,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $provider->id,
        tariffId: $tariff->id,
    );

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => 1200.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

    // Get the invoice item
    $item = $invoice->items->first();
    
    // Assert tariff rate is snapshotted in the item
    expect((float)$item->unit_price)->toBe($originalRate);
    
    // Now change the tariff rate
    $tariff->update([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.25, // New higher rate
        ],
    ]);

    // Refresh the invoice item from database
    $item->refresh();
    
    // Assert the invoice item still has the original rate (snapshotted)
    expect((float)$item->unit_price)->toBe($originalRate);
    expect((float)$item->unit_price)->not->toBe(0.25);
});

test('finalized invoice cannot be modified', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Create tenant (renter)
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'John Doe',
        'email' => 'tenant@test.com',
        'property_id' => $property->id,
        'lease_start' => now()->subMonths(6),
        'lease_end' => now()->addMonths(6),
    ]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create electricity meter
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'EL-000001',
        'type' => MeterType::ELECTRICITY,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create provider and tariff
    $provider = Provider::create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Standard Rate',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);

    // Create meter readings
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();

    $this->attachConsumptionServiceToMeter(
        meter: $meter,
        serviceName: 'Electricity',
        unitOfMeasurement: 'kWh',
        unitRate: $tariff->configuration['rate'],
        bridgeType: ServiceType::ELECTRICITY,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $provider->id,
        tariffId: $tariff->id,
    );

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => 1200.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

    // Finalize the invoice
    $billingService->finalizeInvoice($invoice);
    $invoice->refresh();

    // Assert invoice is finalized
    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED);
    expect($invoice->finalized_at)->not->toBeNull();
    
    // Store original total
    $originalTotal = $invoice->total_amount;

    // Attempt to modify the invoice total should throw exception
    expect(function () use ($invoice) {
        $invoice->total_amount = 999.99;
        $invoice->save();
    })->toThrow(\App\Exceptions\InvoiceAlreadyFinalizedException::class);
    
    // Refresh from database
    $invoice->refresh();
    
    // Assert the total was not changed (protected by exception)
    expect((float)$invoice->total_amount)->toBe((float)$originalTotal);
});

test('finalized invoice is not recalculated when tariffs change', function () {
    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Create property
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'address' => 'Gedimino pr. 15, Apt 1',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.00,
    ]);

    // Create tenant (renter)
    $tenant = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'name' => 'John Doe',
        'email' => 'tenant@test.com',
        'property_id' => $property->id,
        'lease_start' => now()->subMonths(6),
        'lease_end' => now()->addMonths(6),
    ]);

    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    // Create electricity meter
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'serial_number' => 'EL-000001',
        'type' => MeterType::ELECTRICITY,
        'property_id' => $property->id,
        'installation_date' => now()->subYears(2),
        'supports_zones' => false,
    ]);

    // Create provider and tariff
    $provider = Provider::create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $tariff = Tariff::create([
        'provider_id' => $provider->id,
        'name' => 'Standard Rate',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->subYear(),
        'active_until' => null,
    ]);

    // Create meter readings
    $periodStart = now()->startOfMonth();
    $periodEnd = now()->endOfMonth();

    $this->attachConsumptionServiceToMeter(
        meter: $meter,
        serviceName: 'Electricity',
        unitOfMeasurement: 'kWh',
        unitRate: $tariff->configuration['rate'],
        bridgeType: ServiceType::ELECTRICITY,
        effectiveFrom: $periodStart->copy()->subYear(),
        providerId: $provider->id,
        tariffId: $tariff->id,
    );

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodStart->copy()->subDay(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'reading_date' => $periodEnd,
        'value' => 1200.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Generate invoice
    $billingService = app(BillingService::class);
    $invoice = $billingService->generateInvoice($tenant, $periodStart, $periodEnd);

    // Finalize the invoice
    $billingService->finalizeInvoice($invoice);
    $invoice->refresh();

    // Store original values
    $originalTotal = $invoice->total_amount;
    $originalItemPrice = $invoice->items->first()->unit_price;
    $originalItemTotal = $invoice->items->first()->total;

    // Change the tariff rate significantly
    $tariff->update([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.50, // Much higher rate
        ],
    ]);

    // Refresh invoice and items from database
    $invoice->refresh();
    $item = $invoice->items->first();
    $item->refresh();

    // Assert invoice total has not changed
    expect($invoice->total_amount)->toBe($originalTotal);
    
    // Assert invoice item prices have not changed (snapshotted)
    expect($item->unit_price)->toBe($originalItemPrice);
    expect($item->total)->toBe($originalItemTotal);
    
    // Assert the new tariff rate is different (to confirm tariff actually changed)
    $tariff->refresh();
    expect($tariff->configuration['rate'])->toBe(0.50);
    expect($tariff->configuration['rate'])->not->toBe($originalItemPrice);
});
