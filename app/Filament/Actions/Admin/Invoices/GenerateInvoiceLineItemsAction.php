<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Models\PropertyAssignment;
use Carbon\CarbonInterface;

class GenerateInvoiceLineItemsAction
{
    /**
     * @return array{
     *     items: array<int, array{description: string, period_start: string, period_end: string, amount: float}>,
     *     total_amount: float
     * }
     */
    public function handle(
        PropertyAssignment $assignment,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
    ): array {
        $propertyName = $assignment->property?->name ?? __('admin.invoices.empty.property');

        return [
            'items' => [[
                'description' => __('admin.invoices.generated.default_line_item', [
                    'property' => $propertyName,
                    'period_start' => $billingPeriodStart->toDateString(),
                    'period_end' => $billingPeriodEnd->toDateString(),
                ]),
                'period_start' => $billingPeriodStart->toDateString(),
                'period_end' => $billingPeriodEnd->toDateString(),
                'amount' => 0.0,
            ]],
            'total_amount' => 0.0,
        ];
    }
}
