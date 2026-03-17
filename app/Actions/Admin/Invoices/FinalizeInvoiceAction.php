<?php

namespace App\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class FinalizeInvoiceAction
{
    public function __construct(
        private readonly SaveInvoiceDraftAction $saveInvoiceDraftAction,
    ) {}

    public function handle(Invoice $invoice, array $attributes = []): Invoice
    {
        $invoice = $this->saveInvoiceDraftAction->handle($invoice, $attributes);

        $invoice->update([
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => $invoice->finalized_at ?? now(),
        ]);

        return $invoice->fresh();
    }
}
