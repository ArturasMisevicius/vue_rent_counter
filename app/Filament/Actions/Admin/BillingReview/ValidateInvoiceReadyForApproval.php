<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Filament\Support\Admin\BillingReview\InvoiceReadinessResult;
use App\Models\Invoice;

final readonly class ValidateInvoiceReadyForApproval
{
    public function __construct(
        private BuildBillingReviewForPeriod $buildBillingReviewForPeriod,
    ) {}

    public function handle(Invoice $invoice, bool $acceptWarnings = false): InvoiceReadinessResult
    {
        $result = $this->buildBillingReviewForPeriod->readiness($invoice);
        $result->throwIfBlocked($acceptWarnings);

        return $result;
    }
}
