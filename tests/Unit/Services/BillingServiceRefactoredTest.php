<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Exceptions\BillingException;
use App\Exceptions\MissingMeterReadingException;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Services\GyvatukasCalculator;
use App\Services\TariffResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tariffResolver = app(TariffResolver::class);
    $this->gyvatukasCalculator = app(GyvatukasCalculator::class);
    $this->billingService = new BillingService(
        $this->tariffResolver,
        $this->gyvatukasCalculator
    );
});

describe('BillingService Refactored', function () {
    describe('generateInvoice', function () {
        it('generates invoice with all meter types', function () {
            // Arrange
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
            $tenant->update(['property_id' => $property->id]);
            
            $building = Building::factory()->create(['tenant_id' => $tenant->tenant_id]);
            $property->update(['building_id' => $building->id]);
            
            // Create providers and tariffs
            $electricityProvider = Provider::factory()->create(['service_type' => 'electricity']);
            $waterProvider = Provider::factory()->create(['service_type' => 'water']);
            $heatingProvider = Provider::factory()->create(['service_type' => 'heating']);
            
            Tariff::factory()->flat()->create([
                'provider_id' => $electricityProvider->id,
                'active_from' => now()->subMonth(),
            ]);
            Tariff::factory()->flat()->create([
                'provider_id' => $waterProvider->id,
                'active_from' => now()->subMonth(),
            ]);
            Tariff::factory()->flat()->create([
                'provider_id' => $heatingProvider->id,
                'active_from' => now()->subMonth(),
            ]);
            
            // Create meters with readings (include tenant_id for scoping)
            $electricityMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->tenant_id,
                'type' => MeterType::ELECTRICITY,
                'supports_zones' => false,
            ]);
            
            $waterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->tenant_id,
                'type' => MeterType::WATER_COLD,
                'supports_zones' => false,
            ]);
            
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->tenant_id,
                'type' => MeterType::HEATING,
                'supports_zones' => false,
            ]);
            
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd = Carbon::now()->endOfMonth();
            
            // Create readings for each meter
            foreach ([$electricityMeter, $waterMeter, $heatingMeter] as $meter) {
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodStart->copy()->subDay(),
                    'value' => 1000.0,
                ]);
                
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodEnd->copy()->addDay(),
                    'value' => 1500.0,
                ]);
            }
            
            // Act
            $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
            
            // Assert
            expect($invoice)->toBeInstanceOf(Invoice::class);
            expect($invoice->status)->toBe(InvoiceStatus::DRAFT);
            expect($invoice->items)->toHaveCount(5); // 3 consumption + 2 water fixed fees
            expect($invoice->total_amount)->toBeGreaterThan(0);
        });
        
        it('throws exception when tenant has no property', function () {
            $tenant = Tenant::factory()->create(['property_id' => null]);
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd = Carbon::now()->endOfMonth();
            
            expect(fn() => $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd))
                ->toThrow(BillingException::class, 'has no associated property');
        });
        
        it('throws exception when property has no meters', function () {
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
            $tenant->update(['property_id' => $property->id]);
            
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd = Carbon::now()->endOfMonth();
            
            expect(fn() => $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd))
                ->toThrow(BillingException::class, 'has no meters');
        });
        
        it('handles missing meter readings gracefully', function () {
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
            $tenant->update(['property_id' => $property->id]);
            
            $electricityProvider = Provider::factory()->create(['service_type' => 'electricity']);
            Tariff::factory()->flat()->create([
                'provider_id' => $electricityProvider->id,
                'active_from' => now()->subMonth(),
            ]);
            
            Meter::factory()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->tenant_id,
                'type' => MeterType::ELECTRICITY,
            ]);
            
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd = Carbon::now()->endOfMonth();
            
            // Act - should create invoice but skip meters without readings
            $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
            
            // Assert
            expect($invoice)->toBeInstanceOf(Invoice::class);
            expect($invoice->items)->toHaveCount(0);
            expect($invoice->total_amount)->toBe(0.0);
        });
        
        it('includes gyvatukas when building exists', function () {
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
            $tenant->update(['property_id' => $property->id]);
            
            $building = Building::factory()->create([
                'tenant_id' => $tenant->tenant_id,
                'gyvatukas_summer_average' => 150.0,
            ]);
            $property->update(['building_id' => $building->id]);
            
            $heatingProvider = Provider::factory()->create(['service_type' => 'heating']);
            Tariff::factory()->flat()->create([
                'provider_id' => $heatingProvider->id,
                'active_from' => now()->subMonth(),
            ]);
            
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->tenant_id,
                'type' => MeterType::HEATING,
            ]);
            
            $periodStart = Carbon::create(2024, 1, 1); // Winter month
            $periodEnd = Carbon::create(2024, 1, 31);
            
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodStart->copy()->subDay(),
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodEnd->copy()->addDay(),
                'value' => 1500.0,
            ]);
            
            // Act
            $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
            
            // Assert
            expect($invoice->items->where('description', 'like', '%Gyvatukas%'))->toHaveCount(1);
        });
    });
    
    describe('finalizeInvoice', function () {
        it('finalizes draft invoice', function () {
            $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
            
            $finalized = $this->billingService->finalizeInvoice($invoice);
            
            expect($finalized->status)->toBe(InvoiceStatus::FINALIZED);
            expect($finalized->finalized_at)->not->toBeNull();
        });
        
        it('throws exception when invoice already finalized', function () {
            $invoice = Invoice::factory()->create([
                'status' => InvoiceStatus::FINALIZED,
                'finalized_at' => now(),
            ]);
            
            expect(fn() => $this->billingService->finalizeInvoice($invoice))
                ->toThrow(\App\Exceptions\InvoiceAlreadyFinalizedException::class);
        });
    });
    
    describe('multi-zone meters', function () {
        it('handles day/night electricity zones', function () {
            $tenant = Tenant::factory()->create();
            $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
            $tenant->update(['property_id' => $property->id]);
            
            $electricityProvider = Provider::factory()->create(['service_type' => 'electricity']);
            Tariff::factory()->timeOfUse()->create([
                'provider_id' => $electricityProvider->id,
                'active_from' => now()->subMonth(),
            ]);
            
            $meter = Meter::factory()->create([
                'property_id' => $property->id,
                'tenant_id' => $tenant->tenant_id,
                'type' => MeterType::ELECTRICITY,
                'supports_zones' => true,
            ]);
            
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd = Carbon::now()->endOfMonth();
            
            // Day zone readings
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart->copy()->subDay(),
                'value' => 1000.0,
                'zone' => 'day',
            ]);
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd->copy()->addDay(),
                'value' => 1300.0,
                'zone' => 'day',
            ]);
            
            // Night zone readings
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart->copy()->subDay(),
                'value' => 500.0,
                'zone' => 'night',
            ]);
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd->copy()->addDay(),
                'value' => 700.0,
                'zone' => 'night',
            ]);
            
            // Act
            $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
            
            // Assert
            expect($invoice->items)->toHaveCount(2); // Day + Night
            expect($invoice->items->where('description', 'like', '%day%'))->toHaveCount(1);
            expect($invoice->items->where('description', 'like', '%night%'))->toHaveCount(1);
        });
    });
});
