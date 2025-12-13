<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PricingModel;
use App\Exceptions\BillingException;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Exceptions\MissingMeterReadingException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\UniversalCalculationResult;
use App\ValueObjects\UniversalConsumptionData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * BillingService
 *
 * Generates invoices based on property-attached service configurations.
 */
class BillingService extends BaseService
{
    public function __construct(
        private readonly UniversalBillingCalculator $billingCalculator,
    ) {
    }

    /**
     * Generate an invoice for a tenant for a specific billing period.
     *
     * @throws BillingException
     * @throws MissingMeterReadingException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
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

        $this->checkRateLimit('invoice-generation', auth()->id() ?? 0);

        return $this->executeInTransaction(function () use ($tenant, $periodStart, $periodEnd) {
            $this->log('info', 'Starting invoice generation', [
                'tenant_id' => '[REDACTED]',
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            $billingPeriod = new BillingPeriod($periodStart, $periodEnd);

            $invoice = Invoice::create([
                'tenant_id' => $tenant->tenant_id,
                'tenant_renter_id' => $tenant->id,
                'billing_period_start' => $periodStart->toDateString(),
                'billing_period_end' => $periodEnd->toDateString(),
                'due_date' => $periodEnd->copy()->addDays((int) config('billing.invoice.default_due_days', 14)),
                'total_amount' => 0.00,
                'status' => InvoiceStatus::DRAFT,
            ]);

            $invoiceItems = $this->buildInvoiceItemPayloads($tenant, $billingPeriod);

            $totalAmount = 0.0;
            foreach ($invoiceItems as $itemData) {
                $itemData['invoice_id'] = $invoice->id;
                $item = InvoiceItem::create($itemData);
                $totalAmount += (float) $item->total;
            }

            $invoice->update(['total_amount' => round($totalAmount, 2)]);

            $this->log('info', 'Invoice generation completed', [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total_amount,
                'items_count' => $invoiceItems->count(),
            ]);

            return $invoice;
        });
    }

    /**
     * Finalize an invoice, making it immutable.
     *
     * @throws InvoiceAlreadyFinalizedException
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
            'finalized_at' => $invoice->finalized_at?->toDateTimeString(),
        ]);

        return $invoice;
    }

    /**
     * Recalculate a draft invoice in-place using current readings and service configuration.
     */
    public function recalculateDraftInvoice(Invoice $invoice): Invoice
    {
        if (!$invoice->isDraft()) {
            return $invoice;
        }

        $tenant = $invoice->tenant;

        if (!$tenant) {
            throw new BillingException("Invoice {$invoice->id} has no tenant");
        }

        $billingPeriod = new BillingPeriod(
            $invoice->billing_period_start->copy(),
            $invoice->billing_period_end->copy()
        );

        $invoiceItems = $this->buildInvoiceItemPayloads($tenant, $billingPeriod);

        return $this->executeInTransaction(function () use ($invoice, $invoiceItems) {
            $invoice->items()->delete();

            $totalAmount = 0.0;
            foreach ($invoiceItems as $itemData) {
                $itemData['invoice_id'] = $invoice->id;
                $item = InvoiceItem::create($itemData);
                $totalAmount += (float) $item->total;
            }

            $invoice->update(['total_amount' => round($totalAmount, 2)]);

            return $invoice->refresh();
        });
    }

    /**
     * Build invoice items (no invoice_id) for a tenant and period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildInvoiceItemPayloads(Tenant $tenant, BillingPeriod $billingPeriod): Collection
    {
        $property = $tenant->load([
            'property' => function ($query) use ($billingPeriod) {
                $query->with([
                    'serviceConfigurations' => function ($serviceQuery) use ($billingPeriod) {
                        $serviceQuery
                            ->active()
                            ->effectiveOn($billingPeriod->start)
                            ->with([
                                'utilityService',
                                'meters' => function ($meterQuery) use ($billingPeriod) {
                                    $meterQuery->with(['readings' => function ($readingQuery) use ($billingPeriod) {
                                        $readingQuery
                                            ->whereBetween('reading_date', [
                                                $billingPeriod->start->copy()->subMonth(),
                                                $billingPeriod->end->copy()->addMonth(),
                                            ])
                                            ->orderBy('reading_date')
                                            ->select('id', 'meter_id', 'reading_date', 'value', 'zone', 'reading_values');
                                    }]);
                                },
                            ]);
                    },
                ]);
            },
        ])->property;

        if (!$property) {
            throw new BillingException("Tenant {$tenant->id} has no associated property");
        }

        $serviceConfigurations = $property->serviceConfigurations;

        if ($serviceConfigurations->isEmpty()) {
            throw new BillingException("Property {$property->id} has no configured services");
        }

        $invoiceItems = collect();
        foreach ($serviceConfigurations as $serviceConfiguration) {
            $invoiceItems = $invoiceItems->merge(
                $this->generateInvoiceItemsForServiceConfiguration($serviceConfiguration, $billingPeriod)
            );
        }

        return $invoiceItems;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function generateInvoiceItemsForServiceConfiguration(
        ServiceConfiguration $serviceConfiguration,
        BillingPeriod $period
    ): Collection {
        $service = $serviceConfiguration->utilityService;

        if (!$service) {
            throw new BillingException("Service configuration {$serviceConfiguration->id} is missing utility service");
        }

        $items = collect();

        $consumption = UniversalConsumptionData::fromTotal(0.0);
        $meterSnapshots = [];

        if ($serviceConfiguration->requiresConsumptionData()) {
            ['consumption' => $consumption, 'meter_snapshots' => $meterSnapshots] =
                $this->buildConsumptionData($serviceConfiguration, $period);
        }

        $result = $this->billingCalculator->calculateBill($serviceConfiguration, $consumption, $period);

        if ($result->isZero()) {
            return $items;
        }

        if ($serviceConfiguration->pricing_model === PricingModel::TIME_OF_USE && $consumption->hasZoneData()) {
            $zoneBreakdown = $result->getCalculationDetail('zone_breakdown', []);

            foreach ($zoneBreakdown as $zone => $data) {
                $zoneConsumption = (float) ($data['consumption'] ?? 0);
                $zoneRate = (float) ($data['rate'] ?? 0);
                $zoneAmount = (float) ($data['amount'] ?? 0);

                if ($zoneConsumption <= 0 || $zoneAmount <= 0) {
                    continue;
                }

                $items->push($this->toInvoiceItemData(
                    description: "{$service->name} ({$zone})",
                    quantity: $zoneConsumption,
                    unit: $service->unit_of_measurement ?: null,
                    unitPrice: $zoneRate,
                    total: $zoneAmount,
                    snapshot: $this->buildSnapshot($serviceConfiguration, $consumption, $result, $meterSnapshots, [
                        'zone' => $zone,
                    ]),
                ));
            }

            return $items;
        }

        if ($serviceConfiguration->pricing_model === PricingModel::FIXED_MONTHLY) {
            $items->push($this->toInvoiceItemData(
                description: $service->name,
                quantity: 1.0,
                unit: 'month',
                unitPrice: $result->totalAmount,
                total: $result->totalAmount,
                snapshot: $this->buildSnapshot($serviceConfiguration, $consumption, $result, $meterSnapshots),
            ));

            return $items;
        }

        if ($serviceConfiguration->pricing_model === PricingModel::HYBRID) {
            if ($result->fixedAmount > 0.0) {
                $items->push($this->toInvoiceItemData(
                    description: "{$service->name} - Fixed Fee",
                    quantity: 1.0,
                    unit: 'month',
                    unitPrice: $result->fixedAmount,
                    total: $result->fixedAmount,
                    snapshot: $this->buildSnapshot($serviceConfiguration, $consumption, $result, $meterSnapshots, [
                        'component' => 'fixed',
                    ]),
                ));
            }

            if ($result->consumptionAmount > 0.0 && $consumption->getTotalConsumption() > 0.0) {
                $unitRate = (float) $result->getCalculationDetail('unit_rate', 0.0);

                $items->push($this->toInvoiceItemData(
                    description: "{$service->name} - Consumption",
                    quantity: $consumption->getTotalConsumption(),
                    unit: $service->unit_of_measurement ?: null,
                    unitPrice: $unitRate,
                    total: $result->consumptionAmount,
                    snapshot: $this->buildSnapshot($serviceConfiguration, $consumption, $result, $meterSnapshots, [
                        'component' => 'consumption',
                    ]),
                ));
            }

            return $items;
        }

        $quantity = $serviceConfiguration->requiresConsumptionData()
            ? $consumption->getTotalConsumption()
            : 1.0;

        if ($quantity <= 0) {
            return $items;
        }

        $unitPrice = $quantity > 0 ? ($result->totalAmount / $quantity) : $result->totalAmount;

        $items->push($this->toInvoiceItemData(
            description: $service->name,
            quantity: $quantity,
            unit: $serviceConfiguration->requiresConsumptionData() ? ($service->unit_of_measurement ?: null) : 'month',
            unitPrice: $unitPrice,
            total: $result->totalAmount,
            snapshot: $this->buildSnapshot($serviceConfiguration, $consumption, $result, $meterSnapshots),
        ));

        return $items;
    }

    /**
     * @return array{consumption: UniversalConsumptionData, meter_snapshots: array<int, array<string, mixed>>}
     */
    private function buildConsumptionData(ServiceConfiguration $serviceConfiguration, BillingPeriod $period): array
    {
        $meters = $serviceConfiguration->meters;

        if ($meters->isEmpty()) {
            throw new BillingException("Service configuration {$serviceConfiguration->id} requires meters but none are linked");
        }

        $zoneConsumption = [];
        $totalConsumption = 0.0;
        $meterSnapshots = [];

        foreach ($meters as $meter) {
            if ($meter->supports_zones) {
                $zones = $this->getZonesForMeter($meter);

                foreach ($zones as $zone) {
                    $startReading = $this->getReadingAtOrBefore($meter, $zone, $period->start);
                    $endReading = $this->getReadingAtOrAfter($meter, $zone, $period->end);

                    if (!$startReading) {
                        throw new MissingMeterReadingException($meter->id, $period->start, $zone);
                    }

                    if (!$endReading) {
                        throw new MissingMeterReadingException($meter->id, $period->end, $zone);
                    }

                    $consumption = max(0.0, (float) $endReading->value - (float) $startReading->value);
                    $zoneConsumption[$zone] = ($zoneConsumption[$zone] ?? 0.0) + $consumption;
                    $totalConsumption += $consumption;

                    $meterSnapshots[] = $this->buildMeterSnapshot($meter, $zone, $startReading, $endReading, $consumption);
                }

                continue;
            }

            $startReading = $this->getReadingAtOrBefore($meter, null, $period->start);
            $endReading = $this->getReadingAtOrAfter($meter, null, $period->end);

            if (!$startReading) {
                throw new MissingMeterReadingException($meter->id, $period->start, null);
            }

            if (!$endReading) {
                throw new MissingMeterReadingException($meter->id, $period->end, null);
            }

            $consumption = max(0.0, (float) $endReading->value - (float) $startReading->value);
            $totalConsumption += $consumption;

            $meterSnapshots[] = $this->buildMeterSnapshot($meter, null, $startReading, $endReading, $consumption);
        }

        $consumptionData = !empty($zoneConsumption)
            ? UniversalConsumptionData::fromZones(array_map(fn ($v) => round((float) $v, 3), $zoneConsumption))
            : UniversalConsumptionData::fromTotal(round($totalConsumption, 3));

        return [
            'consumption' => $consumptionData,
            'meter_snapshots' => $meterSnapshots,
        ];
    }

    private function buildMeterSnapshot(
        Meter $meter,
        ?string $zone,
        MeterReading $startReading,
        MeterReading $endReading,
        float $consumption
    ): array {
        return [
            'meter_id' => $meter->id,
            'meter_serial' => $meter->serial_number,
            'zone' => $zone,
            'start_reading_id' => $startReading->id,
            'start_value' => number_format((float) $startReading->value, 2, '.', ''),
            'start_date' => $startReading->reading_date->toDateString(),
            'end_reading_id' => $endReading->id,
            'end_value' => number_format((float) $endReading->value, 2, '.', ''),
            'end_date' => $endReading->reading_date->toDateString(),
            'consumption' => round($consumption, 3),
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function buildSnapshot(
        ServiceConfiguration $serviceConfiguration,
        UniversalConsumptionData $consumption,
        UniversalCalculationResult $result,
        array $meterSnapshots,
        array $extra = []
    ): array {
        return array_merge([
            'service_configuration' => $serviceConfiguration->createSnapshot(),
            'utility_service' => [
                'id' => $serviceConfiguration->utilityService->id,
                'name' => $serviceConfiguration->utilityService->name,
                'unit_of_measurement' => $serviceConfiguration->utilityService->unit_of_measurement,
            ],
            'consumption' => $consumption->toArray(),
            'meters' => $meterSnapshots,
            'calculation' => $result->toArray(),
        ], $extra);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function toInvoiceItemData(
        string $description,
        float $quantity,
        ?string $unit,
        float $unitPrice,
        float $total,
        array $snapshot
    ): array {
        return [
            'description' => $description,
            'quantity' => round($quantity, 2),
            'unit' => $unit,
            'unit_price' => round($unitPrice, 4),
            'total' => round($total, 2),
            'meter_reading_snapshot' => $snapshot,
        ];
    }

    private function getReadingAtOrBefore(Meter $meter, ?string $zone, Carbon $date): ?MeterReading
    {
        return $meter->readings
            ->when(
                $zone !== null,
                fn ($c) => $c->where('zone', $zone),
                fn ($c) => $c->whereNull('zone')
            )
            ->filter(fn ($r) => $r->reading_date->lte($date))
            ->sortByDesc('reading_date')
            ->first();
    }

    private function getReadingAtOrAfter(Meter $meter, ?string $zone, Carbon $date): ?MeterReading
    {
        return $meter->readings
            ->when(
                $zone !== null,
                fn ($c) => $c->where('zone', $zone),
                fn ($c) => $c->whereNull('zone')
            )
            ->filter(fn ($r) => $r->reading_date->gte($date))
            ->sortBy('reading_date')
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function getZonesForMeter(Meter $meter): array
    {
        return $meter->readings
            ->whereNotNull('zone')
            ->pluck('zone')
            ->filter(fn ($zone) => is_string($zone) && $zone !== '')
            ->unique()
            ->values()
            ->toArray();
    }

    private function checkRateLimit(string $key, int $userId): void
    {
        $cacheKey = "rate_limit:{$key}:{$userId}";
        $attempts = Cache::get($cacheKey, 0);

        $maxAttempts = (int) config('billing.rate_limit.max_attempts', 10);

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
}
