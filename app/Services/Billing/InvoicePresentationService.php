<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

final class InvoicePresentationService
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
    ) {}

    /**
     * @return array{
     *     invoice_number: string,
     *     currency: string,
     *     status: string,
     *     status_label: string,
     *     status_summary: string,
     *     total_amount: string,
     *     paid_amount: string,
     *     outstanding_amount: string,
     *     total_amount_display: string,
     *     paid_amount_display: string,
     *     outstanding_amount_display: string,
     *     items: array<int, array{
     *         description: string,
     *         quantity: string,
     *         unit: string,
     *         unit_price: string,
     *         unit_price_display: string,
     *         total: string,
     *         total_display: string
     *     }>
     * }
     */
    public function present(Invoice $invoice): array
    {
        $status = $invoice->effectiveStatus();
        $currency = (string) $invoice->currency;
        $totalAmount = $this->calculator->money($invoice->total_amount ?? '0');
        $paidAmount = $this->calculator->money($invoice->normalized_paid_amount);
        $outstandingAmount = $this->calculator->money($invoice->outstanding_balance);

        return [
            'invoice_number' => (string) $invoice->invoice_number,
            'currency' => $currency,
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_summary' => $this->statusSummary($invoice, $status),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount,
            'total_amount_display' => $this->formatCurrency($currency, $totalAmount),
            'paid_amount_display' => $this->formatCurrency($currency, $paidAmount),
            'outstanding_amount_display' => $this->formatCurrency($currency, $outstandingAmount),
            'items' => $this->presentItems($invoice),
        ];
    }

    /**
     * @return array<int, array{
     *     description: string,
     *     quantity: string,
     *     unit: string,
     *     unit_price: string,
     *     unit_price_display: string,
     *     total: string,
     *     total_display: string
     * }>
     */
    private function presentItems(Invoice $invoice): array
    {
        return array_values(array_map(function (mixed $item): array {
            $resolvedItem = is_array($item) ? $item : [];
            $quantity = $this->calculator->quantity($resolvedItem['quantity'] ?? 1);
            $unitPrice = $this->calculator->rate(
                $resolvedItem['unit_price']
                ?? $resolvedItem['rate']
                ?? $resolvedItem['amount']
                ?? $resolvedItem['total']
                ?? '0',
            );
            $total = $this->calculator->money(
                $resolvedItem['total']
                ?? $resolvedItem['amount']
                ?? '0',
            );

            return [
                'description' => (string) ($resolvedItem['description'] ?? ''),
                'quantity' => $quantity,
                'unit' => (string) ($resolvedItem['unit'] ?? ''),
                'unit_price' => $unitPrice,
                'unit_price_display' => $this->calculator->money($unitPrice),
                'total' => $total,
                'total_display' => $total,
            ];
        }, $this->canonicalItems($invoice)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function canonicalItems(Invoice $invoice): array
    {
        $snapshotItems = $invoice->snapshot_data;

        if (is_array($snapshotItems) && $snapshotItems !== []) {
            return array_values(array_filter($snapshotItems, is_array(...)));
        }

        return array_values(array_filter($invoice->items, is_array(...)));
    }

    private function statusSummary(Invoice $invoice, InvoiceStatus $status): string
    {
        if ($status === InvoiceStatus::PAID || $invoice->outstanding_balance <= 0) {
            return __('admin.invoices.status_summaries.paid');
        }

        if ($invoice->normalized_paid_amount > 0) {
            return __('admin.invoices.status_summaries.partially_paid');
        }

        return __('admin.invoices.status_summaries.outstanding');
    }

    private function formatCurrency(string $currency, string $amount): string
    {
        return trim($currency.' '.$amount);
    }
}
