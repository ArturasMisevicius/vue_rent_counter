<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Models\Invoice;
use App\Models\User;

final readonly class RecalculateInvoiceFromReadings
{
    public function __construct(
        private RecalculateInvoice $recalculateInvoice,
    ) {}

    public function handle(Invoice $invoice, ?User $actor = null): Invoice
    {
        return $this->recalculateInvoice->handle($invoice, $actor);
    }
}
