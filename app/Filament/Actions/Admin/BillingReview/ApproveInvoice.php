<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Models\Invoice;
use App\Models\User;

final readonly class ApproveInvoice
{
    public function __construct(
        private ValidateInvoiceReadyForApproval $validateInvoiceReadyForApproval,
        private RecalculateInvoice $recalculateInvoice,
        private FinalizeInvoiceAction $finalizeInvoiceAction,
    ) {}

    public function handle(Invoice $invoice, ?User $actor = null, bool $acceptWarnings = false): Invoice
    {
        $this->validateInvoiceReadyForApproval->handle($invoice, $acceptWarnings);

        $invoice = $this->recalculateInvoice->handle($invoice, $actor);

        return $this->finalizeInvoiceAction->handle($invoice, actor: $actor);
    }
}
