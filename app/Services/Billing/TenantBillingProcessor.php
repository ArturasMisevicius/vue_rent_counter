<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceSnapshotService;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\BillingOptions;
use App\ValueObjects\TenantBillingResult;
use App\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Processes billing for individual tenants.
 * 
 * Handles the creation of invoices for tenants based on their
 * meter readings and service configurations. Ensures proper
 * date handling and invoice creation consistency.
 */
final readonly class TenantBillingProcessor
{
    public function __construct(
        private UniversalServiceProcessor $serviceProcessor,
        private HeatingChargeProcessor $heatingProcessor,
        private InvoiceSnapshotService $snapshotService,
    ) {}

    /**
     * Process billing for a single tenant.
     */
    public function processTenant(
        Tenant $tenant,
        BillingPeriod $period,
        BillingOptions $options
    ): array {
        Log::info('Processing tenant billing', [
            'tenant_id' => $tenant->id,
            'period' => $period->getLabel(),
        ]);

        try {
            // Check if invoice already exists for this period
            $existingInvoice = $this->findExistingInvoice($tenant, $period);
            if ($existingInvoice && !$options->shouldOverwriteExisting()) {
                Log::info('Invoice already exists for tenant', [
                    'tenant_id' => $tenant->id,
                    'invoice_id' => $existingInvoice->id,
                ]);
                
                return [
                    'invoices_generated' => 0,
                    'total_amount' => $existingInvoice->total_amount,
                    'warnings' => [],
                    'errors' => [],
                ];
            }

            // Calculate charges for this tenant
            $totalAmount = 0.0;
            
            // Get the property for this tenant
            $property = $tenant->property;
            if (!$property) {
                throw new \Exception("Tenant {$tenant->id} has no associated property");
            }
            
            // Process universal services
            $serviceResult = $this->serviceProcessor->processUniversalServices(
                $property,
                $period,
                $options
            );
            $totalAmount += $serviceResult['amount'];
            
            // Process heating charges
            $meters = $property->meters()->get();
            $heatingResult = $this->heatingProcessor->processHeatingCharges(
                $property,
                $meters,
                $period
            );
            $totalAmount += $heatingResult['amount'];

            // Create or update invoice
            if ($existingInvoice) {
                $invoice = $this->updateInvoice($existingInvoice, $totalAmount, $period);
                $invoicesGenerated = 0; // Updated existing
            } else {
                $invoice = $this->createInvoice($tenant, $totalAmount, $period, $options);
                $invoicesGenerated = 1; // Created new
            }

            Log::info('Tenant billing processed successfully', [
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'total_amount' => $totalAmount,
            ]);

            return [
                'invoices_generated' => $invoicesGenerated,
                'total_amount' => $totalAmount,
                'warnings' => [],
                'errors' => [],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process tenant billing', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'invoices_generated' => 0,
                'total_amount' => 0.0,
                'warnings' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Find existing invoice for tenant and period.
     */
    private function findExistingInvoice(Tenant $tenant, BillingPeriod $period): ?Invoice
    {
        return Invoice::where('tenant_renter_id', $tenant->id)
            ->where('billing_period_start', $period->getStartDate()->format('Y-m-d H:i:s'))
            ->where('billing_period_end', $period->getEndDate()->format('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Create new invoice for tenant.
     */
    private function createInvoice(
        Tenant $tenant,
        float $totalAmount,
        BillingPeriod $period,
        BillingOptions $options
    ): Invoice {
        // Only create invoice if amount > 0 or if zero invoices are enabled
        if ($totalAmount <= 0 && !$options->shouldCreateZeroInvoices()) {
            Log::info('Skipping zero amount invoice', [
                'tenant_id' => $tenant->id,
                'amount' => $totalAmount,
            ]);
            
            // Create a placeholder invoice with zero amount
            $totalAmount = 0.0;
        }

        $invoice = Invoice::create([
            'tenant_renter_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'billing_period_start' => $period->getStartDate(),
            'billing_period_end' => $period->getEndDate(),
            'total_amount' => $totalAmount,
            'status' => InvoiceStatus::DRAFT->value,
            'generated_at' => now(),
        ]);

        // Create snapshot for historical accuracy
        $this->snapshotService->createInvoiceSnapshot($invoice, $period, $options);

        Log::info('Invoice created with snapshot', [
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'amount' => $totalAmount,
            'has_snapshot' => $invoice->hasSnapshot(),
        ]);

        return $invoice;
    }

    /**
     * Update existing invoice.
     */
    private function updateInvoice(
        Invoice $invoice,
        float $totalAmount,
        BillingPeriod $period
    ): Invoice {
        $invoice->update([
            'total_amount' => $totalAmount,
            'billing_period_start' => $period->getStartDate(),
            'billing_period_end' => $period->getEndDate(),
            'updated_at' => now(),
        ]);

        // Update snapshot for historical accuracy
        $options = BillingOptions::default(); // Use default options for updates
        $this->snapshotService->createInvoiceSnapshot($invoice, $period, $options);

        Log::info('Invoice updated with snapshot', [
            'invoice_id' => $invoice->id,
            'amount' => $totalAmount,
            'has_snapshot' => $invoice->hasSnapshot(),
        ]);

        return $invoice;
    }
}