<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\ValueObjects\BillingOptions;
use App\ValueObjects\BillingPeriod;
use Illuminate\Support\Facades\Log;

/**
 * Processes billing for individual properties.
 * 
 * Coordinates different service charge processors
 * and creates invoices for properties.
 * 
 * @package App\Services\Billing
 */
final readonly class PropertyBillingProcessor
{
    public function __construct(
        private HeatingChargeProcessor $heatingProcessor,
        private UniversalServiceProcessor $universalProcessor,
    ) {}

    /**
     * Process billing for a single property.
     */
    public function processProperty(
        Property $property,
        BillingPeriod $billingPeriod,
        BillingOptions $options
    ): array {
        $results = [
            'invoice_generated' => false,
            'invoice_amount' => 0.0,
            'warnings' => [],
        ];

        // Check if invoice already exists for this period
        if ($this->invoiceExistsForPeriod($property, $billingPeriod) && !$options->shouldRegenerateExisting()) {
            $warning = "Invoice already exists for property {$property->id} in period {$billingPeriod->getLabel()}";
            $results['warnings'][] = $warning;
            return $results;
        }

        // Get meters for this property with eager loading
        $meters = Meter::where('property_id', $property->id)
            ->with(['serviceConfiguration.utilityService', 'readings' => function ($query) use ($billingPeriod) {
                $query->whereBetween('reading_date', [
                    $billingPeriod->getStartDate(),
                    $billingPeriod->getEndDate()
                ])->orderBy('reading_date');
            }])
            ->get();
        
        if ($meters->isEmpty()) {
            $warning = "Property {$property->id} has no meters";
            $results['warnings'][] = $warning;
            return $results;
        }

        // Calculate charges for each service type
        $totalAmount = 0.0;
        $invoiceItems = [];

        // Process heating services
        $heatingResult = $this->heatingProcessor->processHeatingCharges(
            $property,
            $meters,
            $billingPeriod
        );
        $totalAmount += $heatingResult['amount'];
        $invoiceItems = array_merge($invoiceItems, $heatingResult['items']);

        // Process universal services
        $universalResult = $this->universalProcessor->processUniversalServices(
            $property,
            $billingPeriod,
            $options
        );
        $totalAmount += $universalResult['amount'];
        $invoiceItems = array_merge($invoiceItems, $universalResult['items']);

        // Create invoice if there are charges
        if ($totalAmount > 0 || $options->shouldCreateZeroInvoices()) {
            $invoice = $this->createInvoice($property, $billingPeriod, $totalAmount, $invoiceItems, $options);
            
            $results['invoice_generated'] = true;
            $results['invoice_amount'] = $totalAmount;
            $results['invoice_id'] = $invoice->id;
        }

        return $results;
    }

    private function invoiceExistsForPeriod(Property $property, BillingPeriod $billingPeriod): bool
    {
        // Find invoices through the tenant relationship since invoices don't have property_id
        $tenant = $property->tenant()->first();
        if (!$tenant) {
            return false;
        }
        
        return Invoice::where('tenant_renter_id', $tenant->id)
            ->where('billing_period_start', $billingPeriod->getStartDate())
            ->where('billing_period_end', $billingPeriod->getEndDate())
            ->exists();
    }

    private function createInvoice(
        Property $property,
        BillingPeriod $billingPeriod,
        float $totalAmount,
        array $items,
        BillingOptions $options
    ): Invoice {
        $status = $options->shouldAutoApprove() ? InvoiceStatus::FINALIZED : InvoiceStatus::DRAFT;

        // Get the tenant for this property
        $tenant = $property->tenant()->first();
        if (!$tenant) {
            throw new \Exception("Property {$property->id} has no associated tenant");
        }

        $invoice = Invoice::create([
            'tenant_renter_id' => $tenant->id,
            'tenant_id' => $property->tenant_id,
            'billing_period_start' => $billingPeriod->getStartDate(),
            'billing_period_end' => $billingPeriod->getEndDate(),
            'total_amount' => $totalAmount,
            'status' => $status,
            'finalized_at' => $options->shouldAutoApprove() ? now() : null,
        ]);

        // Create invoice items
        foreach ($items as $item) {
            $invoice->items()->create($item);
        }

        Log::info('Invoice created', [
            'invoice_id' => $invoice->id,
            'tenant_renter_id' => $tenant->id,
            'property_id' => $property->id,
            'total_amount' => $totalAmount,
            'status' => $status->value,
        ]);

        return $invoice;
    }
}