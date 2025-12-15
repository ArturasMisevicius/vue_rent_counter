<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceGenerationDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Generate Invoice Action
 * 
 * Single responsibility: Create a single invoice record.
 * Used by BillingService as part of the invoice generation workflow.
 * 
 * @package App\Actions
 */
class GenerateInvoiceAction
{
    /**
     * Execute the action to generate an invoice.
     *
     * @param InvoiceGenerationDTO $dto Invoice generation data
     * @return Invoice The created invoice
     */
    public function execute(InvoiceGenerationDTO $dto): Invoice
    {
        return DB::transaction(function () use ($dto) {
            // Create invoice
            $invoice = Invoice::create([
                'tenant_id' => $dto->tenantId,
                'tenant_renter_id' => $dto->tenantRenterId,
                'billing_period_start' => $dto->periodStart->toDateString(),
                'billing_period_end' => $dto->periodEnd->toDateString(),
                'due_date' => $dto->dueDate->toDateString(),
                'total_amount' => 0.00, // Will be updated after items are added
                'status' => InvoiceStatus::DRAFT,
            ]);

            return $invoice;
        });
    }
}
