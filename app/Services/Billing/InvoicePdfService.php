<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Filament\Support\Admin\Reports\ReportPdfExporter;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoicePdfService
{
    public function __construct(
        private readonly ReportPdfExporter $reportPdfExporter,
    ) {}

    public function streamDownload(Invoice $invoice): StreamedResponse
    {
        $invoice->loadMissing([
            'tenant:id,name,email',
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at',
        ]);

        $summary = [
            ['label' => __('admin.invoices.fields.invoice_number'), 'value' => (string) $invoice->invoice_number],
            ['label' => __('admin.invoices.fields.tenant'), 'value' => (string) ($invoice->tenant?->name ?? __('admin.invoices.empty.tenant'))],
            ['label' => __('admin.invoices.fields.property'), 'value' => (string) ($invoice->property?->name ?? __('admin.invoices.empty.property'))],
            ['label' => __('admin.invoices.fields.status'), 'value' => (string) $invoice->status?->label()],
            ['label' => __('admin.invoices.fields.total_amount'), 'value' => sprintf('%s %s', $invoice->currency, number_format((float) $invoice->total_amount, 2))],
            ['label' => __('admin.invoices.fields.amount_paid'), 'value' => sprintf('%s %s', $invoice->currency, number_format($invoice->normalized_paid_amount, 2))],
        ];

        $rows = collect($invoice->items)
            ->map(fn (array $item): array => [
                'description' => (string) ($item['description'] ?? ''),
                'quantity' => (string) ($item['quantity'] ?? ''),
                'unit_price' => isset($item['unit_price']) ? number_format((float) $item['unit_price'], 2) : '',
                'total' => isset($item['total']) ? number_format((float) $item['total'], 2) : '',
            ])
            ->all();

        $pdf = $this->reportPdfExporter->render(
            title: __('admin.invoices.pdf.title', ['number' => $invoice->invoice_number]),
            summary: $summary,
            columns: [
                ['key' => 'description', 'label' => __('admin.invoices.fields.description')],
                ['key' => 'quantity', 'label' => __('admin.invoices.fields.quantity')],
                ['key' => 'unit_price', 'label' => __('admin.invoices.fields.unit_price')],
                ['key' => 'total', 'label' => __('admin.invoices.fields.total_amount')],
            ],
            rows: $rows,
            emptyState: __('admin.invoices.pdf.empty_items'),
        );

        $filename = Str::slug((string) ($invoice->invoice_number ?: 'invoice')).'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
