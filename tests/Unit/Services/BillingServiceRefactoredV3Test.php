<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Exceptions\BillingException;
use App\Exceptions\InvoiceAlreadyFinalizedException;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->billingService = app(BillingService::class);
    
    // Create providers for each service type
    $this->electricityProvider = Provider::factory()->create([
        'service_type' => 'electricity',
        'name' => 'Ignitis',
    ]);
    
    $this->waterProvider = Provider::factory()->create([
        'service_type' => 'water',
        'name' => 'Vilniaus Vandenys',
    ]);
    
    $this->heatingProvider = Provider::factory()->create([
        'service_type' => 'heating',
        'name' => 'Vilniaus Energija',
    ]);
    
    // Create tariffs
    $this->electricityTariff = Tariff::factory()->create([
        'provider_id' => $this->electricityProvider->id,
        'name' => 'Standard Electricity',
        'configuration' => ['type' => 'flat', 'rate' => 0.15],
        'active_from' => Carbon::now()->subYear(),
    ]);
});

describe('BillingService V3 - Invoice Generation', function () {
    test('generates invoice for tenant with single electricity meter', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        expect($invoice)->toBeInstanceOf(Invoice::class)
            ->and($invoice->tenant_id)->toBe($tenant->tenant_id)
            ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
            ->and($invoice->billing_period_start->toDateString())->toBe($periodStart->toDateString())
            ->and($invoice->billing_period_end->toDateString())->toBe($periodEnd->toDateString())
            ->and($invoice->items)->toHaveCount(1)
            ->and($invoice->total_amount)->toBeGreaterThan(0);
    });

    test('generates invoice with water meter including supply, sewage, and fixed fee', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
            'supports_zones' => false,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 50.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 75.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should have 2 items: consumption + fixed fee
        expect($invoice->items)->toHaveCount(2);
        
        $consumptionItem = $invoice->items->first();
        expect($consumptionItem->quantity)->toBe(25.0)
            ->and($consumptionItem->unit)->toBe('m³');
        
        $fixedFeeItem = $invoice->items->last();
        expect($fixedFeeItem->description)->toContain('Fixed Fee')
            ->and($fixedFeeItem->quantity)->toBe(1.0);
    });

    test('generates invoice with multi-zone electricity meter', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => true,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // Day zone readings
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
            'zone' => 'day',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 1300.0,
            'zone' => 'day',
        ]);

        // Night zone readings
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 500.0,
            'zone' => 'night',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 700.0,
            'zone' => 'night',
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should have 2 items: day zone + night zone
        expect($invoice->items)->toHaveCount(2);
        
        $dayItem = $invoice->items->where('description', 'like', '%day%')->first();
        expect($dayItem)->not->toBeNull()
            ->and($dayItem->quantity)->toBe(300.0);
        
        $nightItem = $invoice->items->where('description', 'like', '%night%')->first();
        expect($nightItem)->not->toBeNull()
            ->and($nightItem->quantity)->toBe(200.0);
    });

    test('generates invoice with gyvatukas for property with building', function () {
        $building = Building::factory()->create([
            'gyvatukas_summer_average' => 150.0,
        ]);
        
        $property = Property::factory()->create([
            'building_id' => $building->id,
        ]);
        
        $tenant = Tenant::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::HEATING,
        ]);

        $periodStart = Carbon::create(2024, 1, 1); // Winter month
        $periodEnd = Carbon::create(2024, 1, 31);

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

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should have heating item + gyvatukas item
        $gyvatukasItem = $invoice->items->where('description', 'like', '%Gyvatukas%')->first();
        expect($gyvatukasItem)->not->toBeNull()
            ->and($gyvatukasItem->total)->toBeGreaterThan(0);
    });

    test('snapshots tariff configuration in invoice items', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $item = $invoice->items->first();
        $snapshot = $item->meter_reading_snapshot;

        expect($snapshot)->toBeArray()
            ->and($snapshot)->toHaveKeys([
                'meter_id',
                'meter_serial',
                'start_reading_id',
                'start_value',
                'start_date',
                'end_reading_id',
                'end_value',
                'end_date',
                'tariff_id',
                'tariff_name',
                'tariff_configuration',
            ])
            ->and($snapshot['tariff_id'])->toBe($this->electricityTariff->id)
            ->and($snapshot['tariff_configuration'])->toBeArray();
    });

    test('snapshots meter readings in invoice items', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'serial_number' => 'TEST-123',
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        $startReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        $endReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $item = $invoice->items->first();
        $snapshot = $item->meter_reading_snapshot;

        expect($snapshot['meter_serial'])->toBe('TEST-123')
            ->and($snapshot['start_reading_id'])->toBe($startReading->id)
            ->and($snapshot['start_value'])->toBe(1000.0)
            ->and($snapshot['end_reading_id'])->toBe($endReading->id)
            ->and($snapshot['end_value'])->toBe(1500.0);
    });
});

describe('BillingService V3 - Error Handling', function () {
    test('throws BillingException when tenant has no property', function () {
        $tenant = Tenant::factory()->create();
        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        expect(fn() => $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd))
            ->toThrow(BillingException::class, 'has no associated property');
    });

    test('throws BillingException when property has no meters', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        expect(fn() => $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd))
            ->toThrow(BillingException::class, 'has no meters');
    });

    test('throws MissingMeterReadingException when start reading is missing', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // Only end reading
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 1500.0,
        ]);

        expect(fn() => $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd))
            ->toThrow(MissingMeterReadingException::class);
    });

    test('throws MissingMeterReadingException when end reading is missing', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // Only start reading
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        expect(fn() => $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd))
            ->toThrow(MissingMeterReadingException::class);
    });

    test('logs warning and continues when meter reading is missing for one meter', function () {
        Log::spy();
        
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        // Meter with readings
        $meter1 = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        // Meter without readings
        $meter2 = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'reading_date' => $periodEnd,
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should create invoice with items from meter1 only
        expect($invoice)->not->toBeNull()
            ->and($invoice->items->count())->toBeGreaterThan(0);
        
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Missing meter reading', \Mockery::on(function ($context) use ($meter2) {
                return $context['meter_id'] === $meter2->id;
            }));
    });
});

describe('BillingService V3 - Invoice Finalization', function () {
    test('finalizes draft invoice successfully', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
        
        expect($invoice->status)->toBe(InvoiceStatus::DRAFT)
            ->and($invoice->finalized_at)->toBeNull();

        $finalizedInvoice = $this->billingService->finalizeInvoice($invoice);

        expect($finalizedInvoice->isFinalized())->toBeTrue()
            ->and($finalizedInvoice->finalized_at)->not->toBeNull()
            ->and($finalizedInvoice->status)->toBe(InvoiceStatus::FINALIZED);
    });

    test('throws InvoiceAlreadyFinalizedException when finalizing already finalized invoice', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
        $this->billingService->finalizeInvoice($invoice);

        expect(fn() => $this->billingService->finalizeInvoice($invoice->fresh()))
            ->toThrow(InvoiceAlreadyFinalizedException::class);
    });

    test('throws InvoiceAlreadyFinalizedException when finalizing paid invoice', function () {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::PAID,
            'finalized_at' => Carbon::now()->subDays(5),
            'paid_at' => Carbon::now()->subDays(2),
        ]);

        expect(fn() => $this->billingService->finalizeInvoice($invoice))
            ->toThrow(InvoiceAlreadyFinalizedException::class);
    });

    test('logs finalization events', function () {
        Log::spy();
        
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
        $this->billingService->finalizeInvoice($invoice);

        Log::shouldHaveReceived('info')
            ->with('Finalizing invoice', \Mockery::on(function ($context) use ($invoice) {
                return $context['invoice_id'] === $invoice->id;
            }));
        
        Log::shouldHaveReceived('info')
            ->with('Invoice finalized', \Mockery::on(function ($context) use ($invoice) {
                return $context['invoice_id'] === $invoice->id
                    && isset($context['finalized_at']);
            }));
    });
});

describe('BillingService V3 - Transaction Management', function () {
    test('rolls back transaction on error during invoice generation', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // Only start reading - will cause MissingMeterReadingException
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        $initialInvoiceCount = Invoice::count();

        try {
            $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);
        } catch (MissingMeterReadingException $e) {
            // Expected exception
        }

        // Invoice should not be created due to rollback
        expect(Invoice::count())->toBe($initialInvoiceCount);
    });

    test('commits transaction on successful invoice generation', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $initialInvoiceCount = Invoice::count();

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        expect(Invoice::count())->toBe($initialInvoiceCount + 1)
            ->and($invoice->exists)->toBeTrue()
            ->and($invoice->items->count())->toBeGreaterThan(0);
    });
});

describe('BillingService V3 - Logging', function () {
    test('logs invoice generation start', function () {
        Log::spy();
        
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        Log::shouldHaveReceived('info')
            ->with('Starting invoice generation', \Mockery::on(function ($context) use ($tenant, $periodStart, $periodEnd) {
                return $context['tenant_id'] === $tenant->id
                    && $context['period_start'] === $periodStart->toDateString()
                    && $context['period_end'] === $periodEnd->toDateString();
            }));
    });

    test('logs invoice creation', function () {
        Log::spy();
        
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        Log::shouldHaveReceived('info')
            ->with('Invoice created', \Mockery::on(function ($context) use ($invoice) {
                return $context['invoice_id'] === $invoice->id;
            }));
    });

    test('logs invoice generation completion', function () {
        Log::spy();
        
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        Log::shouldHaveReceived('info')
            ->with('Invoice generation completed', \Mockery::on(function ($context) use ($invoice) {
                return $context['invoice_id'] === $invoice->id
                    && isset($context['total_amount'])
                    && isset($context['items_count']);
            }));
    });
});

describe('BillingService V3 - Value Objects Integration', function () {
    test('uses BillingPeriod value object correctly', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        expect($invoice->billing_period_start->toDateString())->toBe($periodStart->toDateString())
            ->and($invoice->billing_period_end->toDateString())->toBe($periodEnd->toDateString());
    });

    test('calculates due date correctly', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $expectedDueDate = $periodEnd->copy()->addDays(config('billing.invoice.default_due_days', 14));
        
        expect($invoice->due_date->toDateString())->toBe($expectedDueDate->toDateString());
    });
});

describe('BillingService V3 - Water Billing Calculations', function () {
    test('calculates water bill with supply and sewage rates', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 50.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 75.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $consumptionItem = $invoice->items->where('description', 'not like', '%Fixed Fee%')->first();
        
        $consumption = 25.0;
        $supplyRate = config('billing.water_tariffs.default_supply_rate', 0.97);
        $sewageRate = config('billing.water_tariffs.default_sewage_rate', 1.23);
        $expectedTotal = round($consumption * ($supplyRate + $sewageRate), 2);

        expect($consumptionItem->total)->toBe($expectedTotal);
    });

    test('adds fixed fee for water meters', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::WATER_HOT,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 50.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 75.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $fixedFeeItem = $invoice->items->where('description', 'like', '%Fixed Fee%')->first();
        
        expect($fixedFeeItem)->not->toBeNull()
            ->and($fixedFeeItem->quantity)->toBe(1.0)
            ->and($fixedFeeItem->unit)->toBe('month')
            ->and($fixedFeeItem->total)->toBe(config('billing.water_tariffs.default_fixed_fee', 0.85));
    });

    test('does not add fixed fee for non-water meters', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1500.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $fixedFeeItem = $invoice->items->where('description', 'like', '%Fixed Fee%')->first();
        
        expect($fixedFeeItem)->toBeNull();
    });
});

describe('BillingService V3 - Multiple Meters', function () {
    test('generates invoice with multiple meters of different types', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $electricityMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $waterMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
        ]);

        $heatingMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::HEATING,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // Electricity readings
        MeterReading::factory()->create([
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $electricityMeter->id,
            'reading_date' => $periodEnd,
            'value' => 1500.0,
        ]);

        // Water readings
        MeterReading::factory()->create([
            'meter_id' => $waterMeter->id,
            'reading_date' => $periodStart,
            'value' => 50.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $waterMeter->id,
            'reading_date' => $periodEnd,
            'value' => 75.0,
        ]);

        // Heating readings
        MeterReading::factory()->create([
            'meter_id' => $heatingMeter->id,
            'reading_date' => $periodStart,
            'value' => 500.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $heatingMeter->id,
            'reading_date' => $periodEnd,
            'value' => 800.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should have: electricity (1) + water consumption (1) + water fixed fee (1) + heating (1) = 4 items
        expect($invoice->items->count())->toBeGreaterThanOrEqual(4);
        
        $electricityItem = $invoice->items->where('description', 'like', '%Electricity%')->first();
        expect($electricityItem)->not->toBeNull();
        
        $waterItems = $invoice->items->where('description', 'like', '%Water%');
        expect($waterItems->count())->toBeGreaterThanOrEqual(2); // consumption + fixed fee
        
        $heatingItem = $invoice->items->where('description', 'like', '%Heating%')->first();
        expect($heatingItem)->not->toBeNull();
    });

    test('calculates correct total amount for multiple meters', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter1 = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $meter2 = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::WATER_COLD,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'reading_date' => $periodEnd,
            'value' => 1500.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter2->id,
            'reading_date' => $periodStart,
            'value' => 50.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter2->id,
            'reading_date' => $periodEnd,
            'value' => 75.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        $calculatedTotal = $invoice->items->sum('total');
        
        expect($invoice->total_amount)->toBe(round($calculatedTotal, 2))
            ->and($invoice->total_amount)->toBeGreaterThan(0);
    });
});

describe('BillingService V3 - Edge Cases', function () {
    test('handles zero consumption gracefully', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // Same reading value = zero consumption
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1000.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 1000.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should create invoice but with no consumption items (or zero amount items)
        expect($invoice)->not->toBeNull();
    });

    test('handles negative consumption gracefully', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $periodStart = Carbon::create(2024, 6, 1);
        $periodEnd = Carbon::create(2024, 6, 30);

        // End reading lower than start = negative consumption
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 1500.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 1000.0,
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        // Should handle gracefully (skip item or set to zero)
        expect($invoice)->not->toBeNull();
    });

    test('rounds monetary values to 2 decimal places', function () {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create();
        $tenant->property()->associate($property)->save();

        $meter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
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
            'value' => 1333.33, // Will create non-round consumption
        ]);

        $invoice = $this->billingService->generateInvoice($tenant, $periodStart, $periodEnd);

        expect($invoice->total_amount)->toBeFloat();
        
        // Check that total has at most 2 decimal places
        $decimalPart = explode('.', (string)$invoice->total_amount)[1] ?? '';
        expect(strlen($decimalPart))->toBeLessThanOrEqual(2);
    });
});
