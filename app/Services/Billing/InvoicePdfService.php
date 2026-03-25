<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoicePdfService
{
    public function __construct(
        private readonly InvoicePdfDocumentFactory $invoicePdfDocumentFactory,
        private readonly InvoicePdfRenderer $invoicePdfRenderer,
    ) {}

    public function renderMarkup(Invoice $invoice): string
    {
        return $this->invoicePdfRenderer->renderMarkup(
            $this->invoicePdfDocumentFactory->make($invoice),
        );
    }

    public function renderPdf(Invoice $invoice): string
    {
        return $this->invoicePdfRenderer->render(
            $this->invoicePdfDocumentFactory->make($invoice),
        );
    }

    public function streamDownload(Invoice $invoice): StreamedResponse
    {
        $pdf = $this->renderPdf($invoice);

        $filename = Str::slug((string) ($invoice->invoice_number ?: 'invoice')).'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
