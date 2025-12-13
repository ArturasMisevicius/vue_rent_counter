<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\ServiceType;
use App\Exceptions\BillingException;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Exceptions\MissingMeterReadingException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Provider;
use App\Models\Tenant;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\ConsumptionData;
use App\ValueObjects\InvoiceItemData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BillingService
 * 
 * Orchestrates invoice generation with tariff snapshotting and gyvatukas calculations.
 * 
 * Requirements:
 * - 3.1: Water bill calculation with supply, sewage, and fixed fee
 * - 3.2: Fixed meter subscription fee
 * - 3.3: Property type-specific tariffs
 * - 5.1: Snapshot current tariff rates in invoice items
 * - 5.2: Snapshot meter readings used in calculations
 * - 5.5: Invoice finalization makes invoice immutable
 * 
 * @package App\Services
 */
class BillingService extends BaseService
{
    /**
     * Cache for provider lookups to avoid repeated queries
     * 
     * @var array<string, Provider>
     */
    private array $providerCache = [];

    /**
     * Cache for tariff resolutions to avoid repeated queries
     * 
     * @var array<string, \App\Models\Tariff>
     */
    private array $tariffCache = [];

    /**
     * Cache for config values accessed in loops
     * 
     * @var array<string, mixed>
     */
    private array $configCache = [];

    public function __construct(
        private readonly TariffResolver $tariffResolver,
        private readonly \App\Contracts\GyvatukasCalculatorInterface $gyvatukasCalculator
    ) {
        // Pre-cache frequently accessed config values
        $this->configCache = [
            'water_supply_rate' => config('billing.water_tariffs.default_supply_rate', 0.97),
            'water_sewage_rate' => config('billing.water_tariffs.default_sewage_rate', 1.23),
            'water_fixed_fee' => config('billing.water_tariffs.default_fixed_fee', 0.85),
            'invoice_due_days' => config('billing.invoice.default_due_days', 14),
        ];
    }

    /**
     * Generate an invoice for a tenant for a specific billing period.
     * 
     * This method:
     * 1. Validates authorization
     * 2. Checks rate limits
     * 3. Collects all meter readings for the period
     * 4. Resolves applicable tariffs (snapshots rates)
     * 5. Calculates consumption per utility type
     * 6. Applies GyvatukasCalculator for heating/hot water
     * 7. Creates Invoice with InvoiceItems
     * 8. Returns draft invoice
     * 
     * @param Tenant $tenant The tenant to bill
     * @param Carbon $periodStart Start of billing period
     * @param Carbon $periodEnd End of billing period
     * @return Invoice The generated draft invoice
     * @throws BillingException If invoice generation fails
     * @throws MissingMeterReadingException If required meter readings are missing
     * @throws \Illuminate\Auth\Access\AuthorizationException If unauthorized
     */
    public function generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        // SECURITY: Explicit authorization check
        if (auth()->check() && !auth()->user()->can('create', [Invoice::class, $tenant])) {
            $this->log('warning', 'Unauthorized invoice generation attempt', [
                'user_id' => auth()->id(),
                'tenant_id' => $tenant->id,
                'ip' => request()->ip(),
            ]);
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Unauthorized to generate invoice for this tenant'
            );
        }

        // SECURITY: Rate limiting check
        $this->checkRateLimit('invoice-generation', auth()->id() ?? 0);

        return $this->executeInTransaction(function () use ($tenant, $periodStart, $periodEnd) {
            // SECURITY: Redact sensitive data from logs
            $this->log('info', 'Starting invoice generation', [
                'tenant_id' => '[REDACTED]',
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            // Create billing period value object
            $billingPeriod = new BillingPeriod($periodStart, $periodEnd);

            // Eager load property with building and meters with readings (±7 day buffer)
            // This prevents N+1 queries and loads all necessary data in 2-3 queries
            $property = $tenant->load([
                'property' => function ($query) use ($billingPeriod) {
                    $query->with([
                        'building', // For gyvatukas calculations
                        'meters' => function ($meterQuery) use ($billingPeriod) {
                            $meterQuery->with(['readings' => function ($readingQuery) use ($billingPeriod) {
                                // ±7 day buffer ensures we capture readings at period boundaries
                                $readingQuery->whereBetween('reading_date', [
                                    $billingPeriod->start->copy()->subDays(7),
                                    $billingPeriod->end->copy()->addDays(7)
                                ])
                                ->orderBy('reading_date')
                                ->select('id', 'meter_id', 'reading_date', 'value', 'zone'); // Selective columns
                            }]);
                        }
                    ]);
                }
            ])->property;

            if (!$property) {
                throw new BillingException("Tenant {$tenant->id} has no associated property");
            }

            $meters = $property->meters;
            if ($meters->isEmpty()) {
                throw new BillingException("Property {$property->id} has no meters");
            }

            // Create the invoice
            $invoice = Invoice::create([
                'tenant_id' => $tenant->tenant_id,
                'tenant_renter_id' => $tenant->id,
                'billing_period_start' => $periodStart->toDateString(),
                'billing_period_end' => $periodEnd->toDateString(),
                'due_date' => $periodEnd->copy()->addDays($this->configCache['invoice_due_days']),
                'total_amount' => 0.00,
                'status' => InvoiceStatus::DRAFT,
            ]);

            $this->log('info', 'Invoice created', ['invoice_id' => $invoice->id]);

            // Collect invoice items for all meters
            $invoiceItems = collect();
            $totalAmount = 0.00;

            $hasAnyReadings = false;
            foreach ($meters as $meter) {
                try {
                    $items = $this->generateInvoiceItemsForMeter($meter, $billingPeriod, $property);
                    $invoiceItems = $invoiceItems->merge($items);
                    $hasAnyReadings = true;
                } catch (MissingMeterReadingException $e) {
                    $this->log('warning', 'Missing meter reading', [
                        'meter_id' => $meter->id,
                        'meter_type' => $meter->type->value,
                        'error' => $e->getMessage(),
                    ]);
                    // Re-throw if this is the only meter or if no readings found yet
                    if ($meters->count() === 1 || !$hasAnyReadings) {
                        throw $e;
                    }
                    // Continue with other meters
                }
            }

            // Add gyvatukas items if applicable
            if ($property->building) {
                try {
                    $gyvatukasItems = $this->generateGyvatukasItems($property, $billingPeriod);
                    $invoiceItems = $invoiceItems->merge($gyvatukasItems);
                } catch (\Exception $e) {
                    $this->log('warning', 'Gyvatukas calculation failed', [
                        'building_id' => $property->building_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Create invoice items and calculate total
            foreach ($invoiceItems as $itemData) {
                $itemData['invoice_id'] = $invoice->id;
                $item = InvoiceItem::create($itemData);
                $totalAmount += $item->total;
            }

            // Update invoice total
            $invoice->update(['total_amount' => round($totalAmount, 2)]);

            $this->log('info', 'Invoice generation completed', [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total_amount,
                'items_count' => $invoiceItems->count(),
            ]);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Generate invoice items for a specific meter.
     * 
     * @param Meter $meter The meter to generate items for
     * @param BillingPeriod $period The billing period
     * @param \App\Models\Property $property The property (for tariff selection)
     * @return Collection<int, array<string, mixed>> Collection of invoice item data arrays
     * @throws MissingMeterReadingException If required readings are missing
     */
    private function generateInvoiceItemsForMeter(Meter $meter, BillingPeriod $period, \App\Models\Property $property): Collection
    {
        $items = collect();

        // Handle multi-zone meters (e.g., day/night electricity)
        if ($meter->supports_zones) {
            $zones = $this->getZonesForMeter($meter, $period);
            
            foreach ($zones as $zone) {
                $itemData = $this->createInvoiceItemForZone($meter, $zone, $period, $property);
                if ($itemData) {
                    $items->push($itemData);
                }
            }
        } else {
            // Single zone meter
            $itemData = $this->createInvoiceItemForZone($meter, null, $period, $property);
            if ($itemData) {
                $items->push($itemData);
            }
        }

        // Add fixed fees for water meters
        if (in_array($meter->type, [MeterType::WATER_COLD, MeterType::WATER_HOT])) {
            $fixedFeeItem = $this->createWaterFixedFeeItem($meter);
            $items->push($fixedFeeItem);
        }

        return $items;
    }

    /**
     * Create an invoice item for a specific meter and zone.
     * 
     * @param Meter $meter The meter
     * @param string|null $zone The tariff zone (e.g., 'day', 'night')
     * @param BillingPeriod $period The billing period
     * @param \App\Models\Property $property The property
     * @return array<string, mixed>|null Invoice item data array or null if no consumption
     * @throws MissingMeterReadingException If required readings are missing
     */
    private function createInvoiceItemForZone(Meter $meter, ?string $zone, BillingPeriod $period, \App\Models\Property $property): ?array
    {
        // Get readings for the period
        $startReading = $this->getReadingAtOrBefore($meter, $zone, $period->start);
        $endReading = $this->getReadingAtOrAfter($meter, $zone, $period->end);

        if (!$startReading) {
            throw new MissingMeterReadingException($meter->id, $period->start, $zone);
        }

        if (!$endReading) {
            throw new MissingMeterReadingException($meter->id, $period->end, $zone);
        }

        // Calculate consumption
        $consumptionData = new ConsumptionData($startReading, $endReading, $zone);
        $consumption = $consumptionData->amount();

        // Skip if no consumption
        if ($consumption <= 0) {
            return null;
        }

        // Resolve tariff for this meter type (with caching)
        $provider = $this->getProviderForMeterType($meter->type);
        $tariff = $this->resolveTariffCached($provider, $period->start);

        // Calculate cost based on tariff
        $unitPrice = $this->calculateUnitPrice($meter, $tariff, $consumption, $period->start, $property);
        $total = round($consumption * $unitPrice, 2);

        // Handle water meters with supply + sewage
        if (in_array($meter->type, [MeterType::WATER_COLD, MeterType::WATER_HOT])) {
            $total = $this->calculateWaterTotal($consumption, $property);
            $unitPrice = round($total / $consumption, 4);
        }

        // Create invoice item data
        return [
            'description' => $this->getItemDescription($meter, $zone),
            'quantity' => round($consumption, 2),
            'unit' => $this->getUnit($meter->type),
            'unit_price' => $unitPrice,
            'total' => $total,
            'meter_reading_snapshot' => [
                'meter_id' => $meter->id,
                'meter_serial' => $meter->serial_number,
                'start_reading_id' => $startReading->id,
                'start_value' => number_format((float) $startReading->value, 2, '.', ''),
                'start_date' => $startReading->reading_date->toDateString(),
                'end_reading_id' => $endReading->id,
                'end_value' => number_format((float) $endReading->value, 2, '.', ''),
                'end_date' => $endReading->reading_date->toDateString(),
                'zone' => $zone,
                'tariff_id' => $tariff->id,
                'tariff_name' => $tariff->name,
                'tariff_configuration' => $tariff->configuration,
            ],
        ];
    }

    /**
     * Calculate water bill total including supply, sewage, and fixed fee.
     * 
     * Requirements 3.1, 3.2: Water supply + sewage rates
     * 
     * @param float $consumption Water consumption in m³
     * @param \App\Models\Property $property The property (for type-specific rates)
     * @return float Total cost
     */
    private function calculateWaterTotal(float $consumption, \App\Models\Property $property): float
    {
        // Use cached config values to avoid repeated config() calls
        $supplyRate = $this->configCache['water_supply_rate'];
        $sewageRate = $this->configCache['water_sewage_rate'];

        // Requirement 3.3: Property type-specific tariffs could be applied here
        // For now, using default rates

        $supplyCost = $consumption * $supplyRate;
        $sewageCost = $consumption * $sewageRate;

        return round($supplyCost + $sewageCost, 2);
    }

    /**
     * Create a fixed fee invoice item for water meters.
     * 
     * Requirement 3.2: Fixed meter subscription fee
     * 
     * @param Meter $meter The water meter
     * @return array<string, mixed> Invoice item data array
     */
    private function createWaterFixedFeeItem(Meter $meter): array
    {
        // Use cached config value to avoid repeated config() calls
        $fixedFee = $this->configCache['water_fixed_fee'];

        return [
            'description' => $this->getItemDescription($meter, null) . ' - Fixed Fee',
            'quantity' => 1.00,
            'unit' => 'month',
            'unit_price' => $fixedFee,
            'total' => $fixedFee,
            'meter_reading_snapshot' => [
                'meter_id' => $meter->id,
                'meter_serial' => $meter->serial_number,
                'fee_type' => 'fixed_monthly',
            ],
        ];
    }

    /**
     * Generate gyvatukas (circulation fee) invoice items.
     * 
     * Requirement 4.1, 4.2, 4.3: Gyvatukas calculation
     * 
     * @param \App\Models\Property $property The property
     * @param BillingPeriod $period The billing period
     * @return Collection<int, array<string, mixed>> Collection of gyvatukas invoice item data
     */
    private function generateGyvatukasItems(\App\Models\Property $property, BillingPeriod $period): Collection
    {
        $items = collect();

        if (!$property->building) {
            return $items;
        }

        try {
            $gyvatukasResult = $this->gyvatukasCalculator->calculate(
                $property->building,
                $period->start
            );

            if ($gyvatukasResult > 0) {
                $items->push([
                    'description' => 'Gyvatukas (Hot Water Circulation)',
                    'quantity' => 1.00,
                    'unit' => 'month',
                    'unit_price' => round($gyvatukasResult, 2),
                    'total' => round($gyvatukasResult, 2),
                    'meter_reading_snapshot' => [
                        'building_id' => $property->building_id,
                        'calculation_type' => 'gyvatukas',
                        'calculation_date' => $period->start->toDateString(),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            $this->log('error', 'Gyvatukas calculation failed', [
                'building_id' => $property->building_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $items;
    }

    /**
     * Finalize an invoice, making it immutable.
     * 
     * Requirement 5.5: Invoice finalization makes invoice immutable
     * 
     * @param Invoice $invoice The invoice to finalize
     * @return Invoice The finalized invoice
     * @throws InvoiceAlreadyFinalizedException If invoice is already finalized
     */
    public function finalizeInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->isFinalized() || $invoice->isPaid()) {
            throw new InvoiceAlreadyFinalizedException($invoice->id);
        }

        $this->log('info', 'Finalizing invoice', ['invoice_id' => $invoice->id]);

        $invoice->finalize();

        $this->log('info', 'Invoice finalized', [
            'invoice_id' => $invoice->id,
            'finalized_at' => $invoice->finalized_at->toDateTimeString(),
        ]);

        return $invoice;
    }

    /**
     * Get meter reading at or before a specific date.
     * 
     * Uses already-loaded readings collection to avoid additional queries.
     * 
     * @param Meter $meter The meter (with readings already loaded)
     * @param string|null $zone The zone
     * @param Carbon $date The date
     * @return MeterReading|null The reading or null
     */
    private function getReadingAtOrBefore(Meter $meter, ?string $zone, Carbon $date): ?MeterReading
    {
        // Use already-loaded readings collection to avoid N+1 queries
        return $meter->readings
            ->when($zone !== null, fn($c) => $c->where('zone', $zone), fn($c) => $c->whereNull('zone'))
            ->filter(fn($r) => $r->reading_date->lte($date))
            ->sortByDesc('reading_date')
            ->first();
    }

    /**
     * Get meter reading at or after a specific date.
     * 
     * Uses already-loaded readings collection to avoid additional queries.
     * 
     * @param Meter $meter The meter (with readings already loaded)
     * @param string|null $zone The zone
     * @param Carbon $date The date
     * @return MeterReading|null The reading or null
     */
    private function getReadingAtOrAfter(Meter $meter, ?string $zone, Carbon $date): ?MeterReading
    {
        // Use already-loaded readings collection to avoid N+1 queries
        return $meter->readings
            ->when($zone !== null, fn($c) => $c->where('zone', $zone), fn($c) => $c->whereNull('zone'))
            ->filter(fn($r) => $r->reading_date->gte($date))
            ->sortBy('reading_date')
            ->first();
    }

    /**
     * Get zones for a multi-zone meter within a billing period.
     * 
     * Uses already-loaded readings collection to avoid additional queries.
     * 
     * @param Meter $meter The meter (with readings already loaded)
     * @param BillingPeriod $period The billing period
     * @return array<int, string> Array of zone identifiers
     */
    private function getZonesForMeter(Meter $meter, BillingPeriod $period): array
    {
        // Use already-loaded readings collection to avoid N+1 queries
        return $meter->readings
            ->filter(fn($r) => $r->reading_date->between($period->start, $period->end) && $r->zone !== null)
            ->pluck('zone')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get provider for a specific meter type.
     * 
     * Caches provider lookups to avoid repeated queries.
     * Includes cache integrity validation.
     * 
     * @param MeterType $meterType The meter type
     * @return Provider The provider
     * @throws BillingException If provider not found
     */
    private function getProviderForMeterType(MeterType $meterType): Provider
    {
        $serviceType = match ($meterType) {
            MeterType::ELECTRICITY => ServiceType::ELECTRICITY,
            MeterType::WATER_COLD, MeterType::WATER_HOT => ServiceType::WATER,
            MeterType::HEATING => ServiceType::HEATING,
        };

        $cacheKey = $serviceType->value;

        // SECURITY: Validate cached data integrity
        if (isset($this->providerCache[$cacheKey])) {
            $cached = $this->providerCache[$cacheKey];
            
            // Integrity check: ensure cached object is valid Provider with correct service type
            if (!$cached instanceof Provider || $cached->service_type !== $serviceType) {
                Log::warning('Cache integrity violation detected', [
                    'cache_key' => $cacheKey,
                    'expected_type' => $serviceType->value,
                    'actual_type' => $cached instanceof Provider ? $cached->service_type->value : 'invalid',
                ]);
                unset($this->providerCache[$cacheKey]);
            } else {
                return $cached;
            }
        }

        $provider = Provider::where('service_type', $serviceType)->first();

        if (!$provider) {
            throw new BillingException("No provider found for service type: {$serviceType->value}");
        }

        // Cache for subsequent calls
        $this->providerCache[$cacheKey] = $provider;

        return $provider;
    }

    /**
     * Check rate limit for sensitive operations.
     * 
     * @param string $key Rate limit key
     * @param int $userId User ID
     * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
     */
    private function checkRateLimit(string $key, int $userId): void
    {
        $cacheKey = "rate_limit:{$key}:{$userId}";
        $attempts = Cache::get($cacheKey, 0);
        
        $maxAttempts = config('billing.rate_limit.max_attempts', 10);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Rate limit exceeded', [
                'key' => $key,
                'user_id' => $userId,
                'attempts' => $attempts,
            ]);
            
            throw new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException(
                60,
                'Too many invoice generation attempts. Please try again later.'
            );
        }
        
        Cache::put($cacheKey, $attempts + 1, now()->addMinute());
    }

    /**
     * Calculate unit price for a meter based on tariff.
     * 
     * @param Meter $meter The meter
     * @param \App\Models\Tariff $tariff The tariff
     * @param float $consumption The consumption amount
     * @param Carbon $timestamp The timestamp for time-of-use calculation
     * @param \App\Models\Property $property The property
     * @return float The unit price
     */
    private function calculateUnitPrice(Meter $meter, \App\Models\Tariff $tariff, float $consumption, Carbon $timestamp, \App\Models\Property $property): float
    {
        // For water meters, unit price is calculated differently (supply + sewage)
        if (in_array($meter->type, [MeterType::WATER_COLD, MeterType::WATER_HOT])) {
            // Use cached config values to avoid repeated config() calls
            $supplyRate = $this->configCache['water_supply_rate'];
            $sewageRate = $this->configCache['water_sewage_rate'];
            return round($supplyRate + $sewageRate, 4);
        }

        // For other meters, use tariff resolver
        $totalCost = $this->tariffResolver->calculateCost($tariff, $consumption, $timestamp);
        return $consumption > 0 ? round($totalCost / $consumption, 4) : 0.0;
    }

    /**
     * Resolve tariff with caching to avoid repeated queries.
     * 
     * @param Provider $provider The provider
     * @param Carbon $date The date
     * @return \App\Models\Tariff The resolved tariff
     */
    private function resolveTariffCached(Provider $provider, Carbon $date): \App\Models\Tariff
    {
        $cacheKey = $provider->id . '_' . $date->toDateString();

        // Return cached tariff if available
        if (isset($this->tariffCache[$cacheKey])) {
            return $this->tariffCache[$cacheKey];
        }

        $tariff = $this->tariffResolver->resolve($provider, $date);

        // Cache for subsequent calls
        $this->tariffCache[$cacheKey] = $tariff;

        return $tariff;
    }

    /**
     * Get item description for a meter.
     * 
     * @param Meter $meter The meter
     * @param string|null $zone The zone
     * @return string The description
     */
    private function getItemDescription(Meter $meter, ?string $zone): string
    {
        $description = $meter->type->getLabel();

        if ($zone) {
            $description .= " ({$zone})";
        }

        return $description;
    }

    /**
     * Get unit for a meter type.
     * 
     * @param MeterType $meterType The meter type
     * @return string The unit
     */
    private function getUnit(MeterType $meterType): string
    {
        return match ($meterType) {
            MeterType::ELECTRICITY, MeterType::HEATING => 'kWh',
            MeterType::WATER_COLD, MeterType::WATER_HOT => 'm³',
        };
    }
}
