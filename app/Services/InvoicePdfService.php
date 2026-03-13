<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

/**
 * InvoicePdfService - Generate PDF documents for invoices
 *
 * This service creates downloadable PDF invoices from Invoice models.
 * Uses barryvdh/laravel-dompdf for PDF generation.
 *
 * Requirements:
 * - Generate professional PDF invoices
 * - Include tenant details, billing period, line items, and totals
 * - Preserve invoice data integrity (use snapshotted values)
 *
 * @see \App\Models\Invoice
 * @see \App\Models\InvoiceItem
 */
class InvoicePdfService
{
    /**
     * Generate a PDF for the given invoice.
     *
     * @param Invoice $invoice The invoice to generate PDF for
     * @return DomPDF The generated PDF instance
     */
    public function generate(Invoice $invoice): DomPDF
    {
        // Eager load relationships needed for PDF
        $invoice->loadMissing([
            'items',
            'tenant.property',
            'tenantRenter',
        ]);

        // Generate PDF from Blade view
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        // Configure PDF settings
        $pdf->setPaper('a4', 'portrait');

        // Disable compression for testability
        $pdf->getDomPDF()->set_option('compress', 0);

        return $pdf;
    }

    /**
     * Generate and download a PDF for the given invoice.
     *
     * @param Invoice $invoice The invoice to generate PDF for
     * @param string|null $filename Optional custom filename
     * @return \Illuminate\Http\Response
     */
    public function download(Invoice $invoice, ?string $filename = null): \Illuminate\Http\Response
    {
        $pdf = $this->generate($invoice);

        $filename = $filename ?? $this->generateFilename($invoice);

        return $pdf->download($filename);
    }

    /**
     * Generate and stream a PDF for the given invoice (for inline viewing).
     *
     * @param Invoice $invoice The invoice to generate PDF for
     * @param string|null $filename Optional custom filename
     * @return \Illuminate\Http\Response
     */
    public function stream(Invoice $invoice, ?string $filename = null): \Illuminate\Http\Response
    {
        $pdf = $this->generate($invoice);

        $filename = $filename ?? $this->generateFilename($invoice);

        return $pdf->stream($filename);
    }

    /**
     * Generate a filename for the invoice PDF.
     *
     * @param Invoice $invoice The invoice
     * @return string The generated filename
     */
    protected function generateFilename(Invoice $invoice): string
    {
        $invoiceNumber = $invoice->invoice_number ?? 'INV-' . $invoice->id;
        $invoiceNumber = preg_replace('/[^a-zA-Z0-9-_]/', '_', $invoiceNumber);

        return sprintf('invoice_%s.pdf', $invoiceNumber);
    }
}
