<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Invoice;

class SaveInvoiceDraftAction
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Invoice $invoice, array $attributes): Invoice
    {
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        return $this->billingService->saveDraft($invoice, $attributes);
    }
}
