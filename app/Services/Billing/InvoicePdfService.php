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
        private readonly InvoicePresentationService $invoicePresentationService,
    ) {}

    public function streamDownload(Invoice $invoice): StreamedResponse
    {
        $invoice->loadMissing([
            'tenant:id,name,email',
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at',
        ]);
        $presentation = $this->invoicePresentationService->present($invoice);

        $summary = [
            ['label' => __('admin.invoices.fields.invoice_number'), 'value' => $presentation['invoice_number']],
            ['label' => __('admin.invoices.fields.tenant'), 'value' => (string) ($invoice->tenant?->name ?? __('admin.invoices.empty.tenant'))],
            ['label' => __('admin.invoices.fields.property'), 'value' => (string) ($invoice->property?->name ?? __('admin.invoices.empty.property'))],
            ['label' => __('admin.invoices.fields.status'), 'value' => $presentation['status_label']],
            ['label' => __('admin.invoices.fields.total_amount'), 'value' => $presentation['total_amount_display']],
            ['label' => __('admin.invoices.fields.amount_paid'), 'value' => $presentation['paid_amount_display']],
            ['label' => __('admin.invoices.status_summaries.outstanding'), 'value' => $presentation['outstanding_amount_display']],
        ];

        $rows = collect($presentation['items'])
            ->map(fn (array $item): array => [
                'description' => (string) ($item['description'] ?? ''),
                'quantity' => (string) ($item['quantity'] ?? ''),
                'unit_price' => (string) ($item['unit_price_display'] ?? ''),
                'total' => (string) ($item['total_display'] ?? ''),
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
