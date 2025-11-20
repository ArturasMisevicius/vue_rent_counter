<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestInvoicesSeeder extends Seeder
{
    /**
     * Seed test invoices for all tenants.
     * 
     * Creates invoices in different states:
     * - Draft invoice for current month
     * - Finalized invoice for last month
     * - Paid invoice for 2 months ago
     * 
     * Each invoice includes itemized charges with snapshotted tariff rates.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Create draft invoice for current month
            $this->createDraftInvoice($tenant);

            // Create finalized invoice for last month
            $this->createFinalizedInvoice($tenant);

            // Create paid invoice for 2 months ago
            $this->createPaidInvoice($tenant);
        }
    }

    /**
     * Create a draft invoice for the current month.
     *
     * @param Tenant $tenant
     * @return void
     */
    private function createDraftInvoice(Tenant $tenant): void
    {
        $billingPeriodStart = Carbon::now()->startOfMonth();
        $billingPeriodEnd = Carbon::now()->endOfMonth();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'total_amount' => 0, // Will be calculated from items
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
        ]);

        $this->createInvoiceItems($invoice, $tenant, $billingPeriodStart);
    }

    /**
     * Create a finalized invoice for last month.
     *
     * @param Tenant $tenant
     * @return void
     */
    private function createFinalizedInvoice(Tenant $tenant): void
    {
        $billingPeriodStart = Carbon::now()->subMonth()->startOfMonth();
        $billingPeriodEnd = Carbon::now()->subMonth()->endOfMonth();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'total_amount' => 0, // Will be calculated from items
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => $billingPeriodEnd->copy()->addDay(),
        ]);

        $this->createInvoiceItems($invoice, $tenant, $billingPeriodStart);
    }

    /**
     * Create a paid invoice for 2 months ago.
     *
     * @param Tenant $tenant
     * @return void
     */
    private function createPaidInvoice(Tenant $tenant): void
    {
        $billingPeriodStart = Carbon::now()->subMonths(2)->startOfMonth();
        $billingPeriodEnd = Carbon::now()->subMonths(2)->endOfMonth();

        $invoice = Invoice::create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'total_amount' => 0, // Will be calculated from items
            'status' => InvoiceStatus::PAID,
            'finalized_at' => $billingPeriodEnd->copy()->addDay(),
        ]);

        $this->createInvoiceItems($invoice, $tenant, $billingPeriodStart);
    }

    /**
     * Create invoice items for an invoice based on meter readings.
     *
     * @param Invoice $invoice
     * @param Tenant $tenant
     * @param Carbon $billingPeriodStart
     * @return void
     */
    private function createInvoiceItems(Invoice $invoice, Tenant $tenant, Carbon $billingPeriodStart): void
    {
        $property = $tenant->property;
        $meters = Meter::where('property_id', $property->id)->get();

        $totalAmount = 0;

        foreach ($meters as $meter) {
            // Get readings for this billing period
            $currentReading = MeterReading::where('meter_id', $meter->id)
                ->where('reading_date', '>=', $billingPeriodStart)
                ->orderBy('reading_date', 'asc')
                ->first();

            $previousReading = MeterReading::where('meter_id', $meter->id)
                ->where('reading_date', '<', $billingPeriodStart)
                ->orderBy('reading_date', 'desc')
                ->first();

            if (!$currentReading || !$previousReading) {
                continue; // Skip if no readings available
            }

            // Get tariff for this meter type
            $tariff = $this->getTariffForMeterType($meter->type);
            
            if (!$tariff) {
                continue; // Skip if no tariff found
            }

            // Create invoice items based on meter type
            if ($meter->supports_zones) {
                // Electricity with day/night zones
                $this->createElectricityItems($invoice, $meter, $currentReading, $previousReading, $tariff, $totalAmount);
            } else {
                // Other utility types
                $this->createStandardItem($invoice, $meter, $currentReading, $previousReading, $tariff, $totalAmount);
            }
        }

        // Update invoice total - use DB query to bypass model events for finalized invoices
        if ($invoice->status === InvoiceStatus::DRAFT) {
            $invoice->update(['total_amount' => $totalAmount]);
        } else {
            // For finalized/paid invoices, update directly via DB to bypass model protection
            \DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['total_amount' => $totalAmount]);
        }
    }

    /**
     * Create electricity invoice items with day/night zones.
     *
     * @param Invoice $invoice
     * @param Meter $meter
     * @param MeterReading $currentReading
     * @param MeterReading $previousReading
     * @param Tariff $tariff
     * @param float &$totalAmount
     * @return void
     */
    private function createElectricityItems(
        Invoice $invoice,
        Meter $meter,
        MeterReading $currentReading,
        MeterReading $previousReading,
        Tariff $tariff,
        float &$totalAmount
    ): void {
        $config = $tariff->configuration;
        
        // Get day zone readings
        $dayCurrentReading = MeterReading::where('meter_id', $meter->id)
            ->where('reading_date', $currentReading->reading_date)
            ->where('zone', 'day')
            ->first();
            
        $dayPreviousReading = MeterReading::where('meter_id', $meter->id)
            ->where('reading_date', $previousReading->reading_date)
            ->where('zone', 'day')
            ->first();

        if ($dayCurrentReading && $dayPreviousReading) {
            $dayConsumption = $dayCurrentReading->value - $dayPreviousReading->value;
            $dayRate = $this->getZoneRate($config, 'day');
            $dayTotal = $dayConsumption * $dayRate;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Electricity (Day Rate)',
                'quantity' => $dayConsumption,
                'unit' => 'kWh',
                'unit_price' => $dayRate,
                'total' => $dayTotal,
                'meter_reading_snapshot' => [
                    'meter_id' => $meter->id,
                    'meter_serial' => $meter->serial_number,
                    'zone' => 'day',
                    'previous_reading' => $dayPreviousReading->value,
                    'current_reading' => $dayCurrentReading->value,
                    'reading_date' => $dayCurrentReading->reading_date->toDateString(),
                ],
            ]);

            $totalAmount += $dayTotal;
        }

        // Get night zone readings
        $nightCurrentReading = MeterReading::where('meter_id', $meter->id)
            ->where('reading_date', $currentReading->reading_date)
            ->where('zone', 'night')
            ->first();
            
        $nightPreviousReading = MeterReading::where('meter_id', $meter->id)
            ->where('reading_date', $previousReading->reading_date)
            ->where('zone', 'night')
            ->first();

        if ($nightCurrentReading && $nightPreviousReading) {
            $nightConsumption = $nightCurrentReading->value - $nightPreviousReading->value;
            $nightRate = $this->getZoneRate($config, 'night');
            $nightTotal = $nightConsumption * $nightRate;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Electricity (Night Rate)',
                'quantity' => $nightConsumption,
                'unit' => 'kWh',
                'unit_price' => $nightRate,
                'total' => $nightTotal,
                'meter_reading_snapshot' => [
                    'meter_id' => $meter->id,
                    'meter_serial' => $meter->serial_number,
                    'zone' => 'night',
                    'previous_reading' => $nightPreviousReading->value,
                    'current_reading' => $nightCurrentReading->value,
                    'reading_date' => $nightCurrentReading->reading_date->toDateString(),
                ],
            ]);

            $totalAmount += $nightTotal;
        }
    }

    /**
     * Create a standard invoice item for non-zoned meters.
     *
     * @param Invoice $invoice
     * @param Meter $meter
     * @param MeterReading $currentReading
     * @param MeterReading $previousReading
     * @param Tariff $tariff
     * @param float &$totalAmount
     * @return void
     */
    private function createStandardItem(
        Invoice $invoice,
        Meter $meter,
        MeterReading $currentReading,
        MeterReading $previousReading,
        Tariff $tariff,
        float &$totalAmount
    ): void {
        $consumption = $currentReading->value - $previousReading->value;
        $config = $tariff->configuration;
        
        // Get rate based on meter type
        $rate = $this->getRateForMeterType($meter->type, $config);
        $total = $consumption * $rate;

        // Add fixed fee if applicable
        $fixedFee = $config['fixed_fee'] ?? 0;
        $total += $fixedFee;

        $description = $this->getDescriptionForMeterType($meter->type);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $description,
            'quantity' => $consumption,
            'unit' => $this->getUnitForMeterType($meter->type),
            'unit_price' => $rate,
            'total' => $total,
            'meter_reading_snapshot' => [
                'meter_id' => $meter->id,
                'meter_serial' => $meter->serial_number,
                'previous_reading' => $previousReading->value,
                'current_reading' => $currentReading->value,
                'reading_date' => $currentReading->reading_date->toDateString(),
                'fixed_fee' => $fixedFee,
            ],
        ]);

        $totalAmount += $total;
    }

    /**
     * Get tariff for a specific meter type.
     *
     * @param MeterType $meterType
     * @return Tariff|null
     */
    private function getTariffForMeterType(MeterType $meterType): ?Tariff
    {
        $providerName = match($meterType) {
            MeterType::ELECTRICITY => 'Ignitis',
            MeterType::WATER_COLD, MeterType::WATER_HOT => 'Vilniaus Vandenys',
            MeterType::HEATING => 'Vilniaus Energija',
        };

        $provider = Provider::where('name', $providerName)->first();
        
        if (!$provider) {
            return null;
        }

        return Tariff::where('provider_id', $provider->id)
            ->where('active_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('active_until')
                    ->orWhere('active_until', '>=', Carbon::now());
            })
            ->orderBy('active_from', 'desc')
            ->first();
    }

    /**
     * Get rate for a specific zone from tariff configuration.
     *
     * @param array $config
     * @param string $zone
     * @return float
     */
    private function getZoneRate(array $config, string $zone): float
    {
        $zones = $config['zones'] ?? [];
        
        foreach ($zones as $zoneConfig) {
            if ($zoneConfig['id'] === $zone) {
                return $zoneConfig['rate'];
            }
        }

        return 0.0;
    }

    /**
     * Get rate for a specific meter type from tariff configuration.
     *
     * @param MeterType $meterType
     * @param array $config
     * @return float
     */
    private function getRateForMeterType(MeterType $meterType, array $config): float
    {
        return match($meterType) {
            MeterType::WATER_COLD => $config['supply_rate'] ?? 0.0,
            MeterType::WATER_HOT => $config['supply_rate'] ?? 0.0,
            MeterType::HEATING => $config['rate'] ?? 0.0,
            default => 0.0,
        };
    }

    /**
     * Get description for a meter type.
     *
     * @param MeterType $meterType
     * @return string
     */
    private function getDescriptionForMeterType(MeterType $meterType): string
    {
        return match($meterType) {
            MeterType::WATER_COLD => 'Cold Water Supply',
            MeterType::WATER_HOT => 'Hot Water Supply',
            MeterType::HEATING => 'Heating',
            default => 'Utility Charge',
        };
    }

    /**
     * Get unit for a meter type.
     *
     * @param MeterType $meterType
     * @return string
     */
    private function getUnitForMeterType(MeterType $meterType): string
    {
        return match($meterType) {
            MeterType::ELECTRICITY => 'kWh',
            MeterType::WATER_COLD, MeterType::WATER_HOT => 'm³',
            MeterType::HEATING => 'kWh',
        };
    }
}
