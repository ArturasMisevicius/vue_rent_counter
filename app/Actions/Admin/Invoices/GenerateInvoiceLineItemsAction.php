<?php

namespace App\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\PropertyAssignment;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class GenerateInvoiceLineItemsAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(
        PropertyAssignment $assignment,
        CarbonInterface|string $billingPeriodStart,
        CarbonInterface|string $billingPeriodEnd,
    ): array {
        $billingStart = $billingPeriodStart instanceof CarbonInterface
            ? $billingPeriodStart->copy()
            : Carbon::parse($billingPeriodStart);
        $billingEnd = $billingPeriodEnd instanceof CarbonInterface
            ? $billingPeriodEnd->copy()
            : Carbon::parse($billingPeriodEnd);

        return [
            'organization_id' => $assignment->organization_id,
            'property_id' => $assignment->property_id,
            'tenant_user_id' => $assignment->tenant_user_id,
            'invoice_number' => sprintf(
                'INV-%s-%s',
                $billingStart->format('Ym'),
                str_pad((string) $assignment->id, 4, '0', STR_PAD_LEFT),
            ),
            'billing_period_start' => $billingStart->toDateString(),
            'billing_period_end' => $billingEnd->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'currency' => 'EUR',
            'total_amount' => 0,
            'amount_paid' => 0,
            'due_date' => $billingEnd->copy()->addDays(14)->toDateString(),
            'finalized_at' => null,
            'paid_at' => null,
            'document_path' => null,
            'notes' => null,
        ];
    }
}
