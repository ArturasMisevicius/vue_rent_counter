<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\ServiceType;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Exceptions\MissingMeterReadingException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\BillingCalculation\BillingCalculatorFactory;
use App\ValueObjects\InvoiceItemData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating and managing utility invoices.
 * 
 * Handles invoice generation with snapshotted tariff rates and meter readings,
 * integrates with TariffResolver and GyvatukasCalculator for accurate billing.
 */
class BillingService
{
    /**
     * Create a new BillingService instance.
     */
    public function __construct(
        private TariffResolver $tariffResolver,
        private BillingCalculatorFactory $calculatorFactory
    ) {}

    /**
     * Generate an invoice for a tenant for a given billing period.
     * 
     * Creates a draft invoice with itemized charges for all utility types,
     * snapshotting current tariff rates and meter readings.
     *
     * @param Tenant $tenant The tenant to bill
     * @param Carbon $periodStart Start of billing period
     * @param Carbon $periodEnd End of billing period
     * @return Invoice The generated draft invoice
     */
    public function generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        return DB::transaction(function () use ($tenant, $periodStart, $periodEnd) {
            // Create draft invoice
            $invoice = Invoice::create([
                'tenant_id' => $tenant->tenant_id,
                'tenant_renter_id' => $tenant->id,
                'billing_period_start' => $periodStart,
                'billing_period_end' => $periodEnd,
                'total_amount' => 0,
                'status' => InvoiceStatus::DRAFT,
            ]);

            $totalAmount = 0;

            // Get all meters for the tenant's property
            $meters = $tenant->property->meters;

            foreach ($meters as $meter) {
                $itemAmount = $this->processMeters($invoice, $meter, $periodStart, $periodEnd);
                $totalAmount += $itemAmount;
            }

            // Update invoice total
            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Process a meter and create invoice items for it.
     *
     * @param Invoice $invoice
     * @param Meter $meter
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return float Total amount for this meter
     */
    private function processMeters(Invoice $invoice, Meter $meter, Carbon $periodStart, Carbon $periodEnd): float
    {
        $totalAmount = 0;

        // Get readings for the period
        $startReading = $this->getReadingAtOrBefore($meter, $periodStart);
        $endReading = $this->getReadingAtOrBefore($meter, $periodEnd);

        if (!$startReading || !$endReading) {
            // Log missing readings for monitoring
            \Log::warning("Missing meter readings for billing", [
                'meter_id' => $meter->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'has_start' => (bool)$startReading,
                'has_end' => (bool)$endReading,
            ]);
            return 0;
        }

        // Handle multi-zone meters (electricity with day/night)
        if ($meter->supports_zones) {
            $zones = $this->getZonesForMeter($meter, $periodStart, $periodEnd);
            
            foreach ($zones as $zone) {
                $zoneAmount = $this->processZone($invoice, $meter, $zone, $periodStart, $periodEnd);
                $totalAmount += $zoneAmount;
            }
        } else {
            // Single zone meter
            $consumption = $endReading->value - $startReading->value;
            
            if ($consumption > 0) {
                $amount = $this->calculateAndCreateItem(
                    $invoice,
                    $meter,
                    $consumption,
                    $periodStart,
                    $periodEnd,
                    $startReading,
                    $endReading,
                    null
                );
                $totalAmount += $amount;
            }
        }

        return $totalAmount;
    }

    /**
     * Process a specific zone for a multi-zone meter.
     *
     * @param Invoice $invoice
     * @param Meter $meter
     * @param string $zone
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return float Amount for this zone
     */
    private function processZone(Invoice $invoice, Meter $meter, string $zone, Carbon $periodStart, Carbon $periodEnd): float
    {
        $startReading = $this->getReadingAtOrBefore($meter, $periodStart, $zone);
        $endReading = $this->getReadingAtOrBefore($meter, $periodEnd, $zone);

        if (!$startReading || !$endReading) {
            return 0;
        }

        $consumption = $endReading->value - $startReading->value;

        if ($consumption <= 0) {
            return 0;
        }

        return $this->calculateAndCreateItem(
            $invoice,
            $meter,
            $consumption,
            $periodStart,
            $periodEnd,
            $startReading,
            $endReading,
            $zone
        );
    }

    /**
     * Calculate cost and create invoice item.
     *
     * @param Invoice $invoice
     * @param Meter $meter
     * @param float $consumption
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param mixed $startReading
     * @param mixed $endReading
     * @param string|null $zone
     * @return float Item total
     */
    private function calculateAndCreateItem(
        Invoice $invoice,
        Meter $meter,
        float $consumption,
        Carbon $periodStart,
        Carbon $periodEnd,
        $startReading,
        $endReading,
        ?string $zone
    ): float {
        // Get provider for this meter type
        $provider = $this->getProviderForMeterType($meter->type);
        
        if (!$provider) {
            \Log::warning("No provider found for meter type", [
                'meter_id' => $meter->id,
                'meter_type' => $meter->type->value,
            ]);
            return 0;
        }

        // Resolve tariff for the billing period
        $tariff = $this->tariffResolver->resolve($provider, $periodEnd);

        // Use factory to get appropriate calculator
        $calculator = $this->calculatorFactory->create($meter->type);
        $result = $calculator->calculate(
            $meter,
            $consumption,
            $tariff,
            $periodStart,
            $invoice->tenant->property
        );

        // Create value object to encapsulate invoice item data
        $itemData = new InvoiceItemData(
            meter: $meter,
            consumption: $consumption,
            unitPrice: $result['unit_price'],
            total: $result['total'],
            startReading: $startReading,
            endReading: $endReading,
            tariff: $tariff,
            zone: $zone
        );

        // Create invoice item with snapshotted data
        InvoiceItem::create($itemData->toArray($invoice->id));

        return $itemData->total;
    }



    /**
     * Get the most recent reading at or before a given date.
     *
     * @param Meter $meter
     * @param Carbon $date
     * @param string|null $zone
     * @return mixed
     */
    private function getReadingAtOrBefore(Meter $meter, Carbon $date, ?string $zone = null)
    {
        $query = $meter->readings()
            ->where('reading_date', '<=', $date)
            ->orderBy('reading_date', 'desc');

        if ($zone !== null) {
            $query->where('zone', $zone);
        } elseif (!$meter->supports_zones) {
            $query->whereNull('zone');
        }

        return $query->first();
    }

    /**
     * Get all zones that have readings for a meter in a period.
     *
     * @param Meter $meter
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return array
     */
    private function getZonesForMeter(Meter $meter, Carbon $periodStart, Carbon $periodEnd): array
    {
        return $meter->readings()
            ->whereBetween('reading_date', [$periodStart, $periodEnd])
            ->whereNotNull('zone')
            ->distinct()
            ->pluck('zone')
            ->toArray();
    }

    /**
     * Get provider for a given meter type.
     *
     * @param MeterType $meterType
     * @return Provider|null
     */
    private function getProviderForMeterType(MeterType $meterType): ?Provider
    {
        $serviceType = match ($meterType) {
            MeterType::ELECTRICITY => ServiceType::ELECTRICITY,
            MeterType::WATER_COLD, MeterType::WATER_HOT => ServiceType::WATER,
            MeterType::HEATING => ServiceType::HEATING,
        };

        return Provider::where('service_type', $serviceType)->first();
    }



    /**
     * Finalize an invoice, making it immutable.
     * 
     * Delegates to InvoiceService for proper validation and finalization.
     * 
     * @deprecated Use InvoiceService::finalize() directly
     * @param Invoice $invoice The invoice to finalize
     * @return void
     * @throws InvoiceAlreadyFinalizedException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function finalizeInvoice(Invoice $invoice): void
    {
        app(InvoiceService::class)->finalize($invoice);
    }
}
