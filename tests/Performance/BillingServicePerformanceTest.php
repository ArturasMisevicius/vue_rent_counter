<?php

use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('BillingService Performance', function () {
    beforeEach(function () {
        $this->billingService = app(BillingService::class);
        
        // Create providers and tariffs
        $this->electricityProvider = Provider::factory()->create([
            'service_type' => 'electricity',
        ]);
        
        $this->waterProvider = Provider::factory()->create([
            'service_type' => 'water',
        ]);
        
        $this->heatingProvider = Provider::factory()->create([
            'service_type' => 'heating',
        ]);
        
        // Create tariffs
        Tariff::factory()->create([
            'provider_id' => $this->electricityProvider->id,
            'name' => 'Electricity Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15],
            'active_from' => now()->subYear(),
        ]);
        
        Tariff::factory()->create([
            'provider_id' => $this->waterProvider->id,
            'name' => 'Water Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.97],
            'active_from' => now()->subYear(),
        ]);
        
        Tariff::factory()->create([
            'provider_id' => $this->heatingProvider->id,
            'name' => 'Heating Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.12],
            'active_from' => now()->subYear(),
        ]);
    });

    test('optimized query count for typical invoice', function () {
        // Create building with property and 10 meters
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        
        $meters = collect();
        for ($i = 0; $i < 10; $i++) {
            $meterType = match ($i % 3) {
                0 => 'electricity',
                1 => 'water_cold',
                2 => 'heating',
            };
            
            $meter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => $meterType,
                'supports_zones' => false,
            ]);
            
            $meters->push($meter);
            
            // Create readings for billing period
            $periodStart = Carbon::create(2024, 6, 1);
            $periodEnd = Carbon::create(2024, 6, 30);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Generate invoice
        $invoice = $this->billingService->generateInvoice(
            $tenant,
            Carbon::create(2024, 6, 1),
            Carbon::create(2024, 6, 30)
        );
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Should be ≤15 queries (was 50-100 before optimization)
        // 1. Load tenant with property, building, meters, and readings (eager loading)
        // 2-4. Provider lookups (3 providers, cached after first lookup)
        // 5-7. Tariff resolutions (3 tariffs, cached after first resolution)
        // 8. Create invoice
        // 9-18. Create invoice items (10 items)
        // 19. Update invoice total
        // 20. Refresh invoice with items
        expect(count($queries))->toBeLessThanOrEqual(15);
        expect($invoice)->toBeInstanceOf(\App\Models\Invoice::class);
        expect($invoice->items)->toHaveCount(13); // 10 meters + 3 water fixed fees
    });

    test('provider caching reduces queries', function () {
        // Create building with property and multiple meters of same type
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        
        // Create 5 electricity meters (same provider)
        for ($i = 0; $i < 5; $i++) {
            $meter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'electricity',
                'supports_zones' => false,
            ]);
            
            $periodStart = Carbon::create(2024, 6, 1);
            $periodEnd = Carbon::create(2024, 6, 30);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Generate invoice
        $invoice = $this->billingService->generateInvoice(
            $tenant,
            Carbon::create(2024, 6, 1),
            Carbon::create(2024, 6, 30)
        );
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Count provider queries (should be 1, not 5)
        $providerQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'providers') && 
                   str_contains($query['query'], 'service_type');
        })->count();
        
        expect($providerQueries)->toBeLessThanOrEqual(1); // Cached after first lookup
        expect($invoice->items)->toHaveCount(5); // 5 electricity meters
    });

    test('tariff caching reduces queries', function () {
        // Create building with property and multiple meters
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        
        // Create 5 meters of different types
        for ($i = 0; $i < 5; $i++) {
            $meterType = match ($i % 3) {
                0 => 'electricity',
                1 => 'water_cold',
                2 => 'heating',
            };
            
            $meter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => $meterType,
                'supports_zones' => false,
            ]);
            
            $periodStart = Carbon::create(2024, 6, 1);
            $periodEnd = Carbon::create(2024, 6, 30);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Generate invoice
        $invoice = $this->billingService->generateInvoice(
            $tenant,
            Carbon::create(2024, 6, 1),
            Carbon::create(2024, 6, 30)
        );
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Count tariff queries (should be 3, not 5)
        $tariffQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'tariffs') && 
                   str_contains($query['query'], 'provider_id');
        })->count();
        
        expect($tariffQueries)->toBeLessThanOrEqual(3); // One per provider type, cached
        expect($invoice->items)->toHaveCount(7); // 5 meters + 2 water fixed fees
    });

    test('collection based reading lookups avoid N+1', function () {
        // Create building with property and 10 meters
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        $tenant = Tenant::factory()->create(['property_id' => $property->id]);
        
        for ($i = 0; $i < 10; $i++) {
            $meter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'electricity',
                'supports_zones' => false,
            ]);
            
            $periodStart = Carbon::create(2024, 6, 1);
            $periodEnd = Carbon::create(2024, 6, 30);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Generate invoice
        $invoice = $this->billingService->generateInvoice(
            $tenant,
            Carbon::create(2024, 6, 1),
            Carbon::create(2024, 6, 30)
        );
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Count meter reading queries (should be 1 eager load, not 20 individual queries)
        $readingQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'meter_readings') && 
                   str_contains($query['query'], 'reading_date');
        })->count();
        
        expect($readingQueries)->toBeLessThanOrEqual(1); // Eager loaded with ±7 day buffer
        expect($invoice->items)->toHaveCount(10); // 10 electricity meters
    });

    test('batch processing maintains performance', function () {
        // Create 5 tenants with properties and meters
        $tenants = collect();
        
        for ($t = 0; $t < 5; $t++) {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);
            $tenant = Tenant::factory()->create(['property_id' => $property->id]);
            
            $tenants->push($tenant);
            
            // Create 5 meters per tenant
            for ($i = 0; $i < 5; $i++) {
                $meterType = match ($i % 3) {
                    0 => 'electricity',
                    1 => 'water_cold',
                    2 => 'heating',
                };
                
                $meter = Meter::factory()->create([
                    'property_id' => $property->id,
                    'type' => $meterType,
                    'supports_zones' => false,
                ]);
                
                $periodStart = Carbon::create(2024, 6, 1);
                $periodEnd = Carbon::create(2024, 6, 30);
                
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodStart,
                    'value' => 1000.0,
                ]);
                
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodEnd,
                    'value' => 2000.0,
                ]);
            }
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Generate invoices for all tenants
        $invoices = collect();
        foreach ($tenants as $tenant) {
            $invoice = $this->billingService->generateInvoice(
                $tenant,
                Carbon::create(2024, 6, 1),
                Carbon::create(2024, 6, 30)
            );
            $invoices->push($invoice);
        }
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Should be ≤75 queries for 5 invoices (≤15 per invoice)
        // With caching, should be even less due to provider/tariff cache hits
        expect(count($queries))->toBeLessThanOrEqual(75);
        expect($invoices)->toHaveCount(5);
        
        // Verify all invoices have items
        foreach ($invoices as $invoice) {
            expect($invoice->items->count())->toBeGreaterThan(0);
        }
    });
});
