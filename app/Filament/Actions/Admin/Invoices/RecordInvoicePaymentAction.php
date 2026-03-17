<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Models\Invoice;

class RecordInvoicePaymentAction
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
    ) {}

    public function handle(Invoice $invoice, array $attributes): Invoice
    {
        return $this->billingService->applyPayment($invoice, $attributes, auth()->user());
    }
}
