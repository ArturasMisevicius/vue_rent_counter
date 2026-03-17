<?php

namespace App\Actions\Admin\Invoices;

use App\Models\Property;
use App\Support\Admin\Invoices\InvoiceLineItemCalculator;
use Illuminate\Support\Carbon;

class GenerateInvoiceLineItemsAction
{
    public function __construct(
        private readonly InvoiceLineItemCalculator $calculator,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(Property $property, Carbon $periodStart, Carbon $periodEnd): array
    {
        return $this->calculator->handle($property, $periodStart, $periodEnd);
    }
}
