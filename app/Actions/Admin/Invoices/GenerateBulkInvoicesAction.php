<?php

namespace App\Actions\Admin\Invoices;

use App\Models\Organization;
use App\Models\User;
use App\Support\Admin\Invoices\BulkInvoicePreviewBuilder;

class GenerateBulkInvoicesAction
{
    public function __construct(
        private readonly BulkInvoicePreviewBuilder $bulkInvoicePreviewBuilder,
        private readonly SaveInvoiceDraftAction $saveInvoiceDraftAction,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{created: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    public function handle(Organization $organization, array $attributes, User $actor): array
    {
        $preview = $this->bulkInvoicePreviewBuilder->handle($organization, $attributes);

        $created = collect($preview['valid'])
            ->map(function (array $row) use ($organization, $attributes, $actor): array {
                $invoice = $this->saveInvoiceDraftAction->handle($organization, [
                    'property_id' => $row['property_id'],
                    'tenant_user_id' => $row['tenant_user_id'],
                    'billing_period_start' => $attributes['billing_period_start'],
                    'billing_period_end' => $attributes['billing_period_end'],
                    'due_date' => $attributes['due_date'] ?? null,
                    'items' => $row['items'],
                ], $actor);

                return [
                    'invoice_id' => $invoice->id,
                    'tenant_user_id' => $invoice->tenant_user_id,
                    'property_id' => $invoice->property_id,
                    'total' => (float) $invoice->total_amount,
                ];
            })
            ->all();

        return [
            'created' => $created,
            'skipped' => $preview['skipped'],
        ];
    }
}
