<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Filament\Support\Admin\Invoices\InvoiceApprovalValidator;
use App\Models\Invoice;

final class ValidateInvoiceCalculationBeforeApproval
{
    public function __construct(
        private readonly InvoiceApprovalValidator $validator,
    ) {}

    /**
     * @return array{
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>
     * }
     */
    public function handle(Invoice $invoice, bool $allowWarnings = false): array
    {
        return $this->validator->ensureCanApprove($invoice, $allowWarnings);
    }

    /**
     * @return array{
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>
     * }
     */
    public function __invoke(Invoice $invoice, bool $allowWarnings = false): array
    {
        return $this->handle($invoice, $allowWarnings);
    }
}
