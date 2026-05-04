<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Invoice;
use App\Models\InvoicePayment;

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
     *     billing_period_start_display: string,
     *     billing_period_end_display: string,
     *     due_date_display: string,
     *     property_name: string,
     *     building_name: string,
     *     tenant_name: string,
     *     total_amount: string,
     *     paid_amount: string,
     *     outstanding_amount: string,
     *     total_amount_display: string,
     *     paid_amount_display: string,
     *     outstanding_amount_display: string,
     *     subtotal_display: string,
     *     items: array<int, array{
     *         description: string,
     *         period: string,
     *         quantity: string,
     *         unit: string,
     *         unit_price: string,
     *         unit_price_display: string,
     *         total: string,
     *         total_display: string,
     *         is_adjustment: bool
     *     }>,
     *     payments: array<int, array{
     *         method_label: string,
     *         paid_at_display: string,
     *         amount_display: string,
     *         reference: string,
     *         notes: string
     *     }>
     * }
     */
    public function present(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'tenant:id,organization_id,name,email',
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at,notes',
            'invoiceItems:id,invoice_id,description,quantity,unit,unit_price,total,meter_reading_snapshot',
        ]);

        $status = $invoice->effectiveStatus();
        $currency = (string) $invoice->currency;
        $totalAmount = $this->calculator->money($invoice->total_amount ?? '0');
        $paidAmount = $this->calculator->money($invoice->normalized_paid_amount);
        $outstandingAmount = $this->calculator->money($invoice->outstanding_balance);
        $items = $this->presentItems($invoice, $currency);
        $subtotal = $this->calculator->money(
            $this->calculator->sum(
                array_map(
                    fn (array $item): string => (string) ($item['total'] ?? '0'),
                    $items,
                ),
                6,
            ),
        );

        return [
            'invoice_number' => (string) $invoice->invoice_number,
            'currency' => $currency,
            'status' => $status->value,
            'status_label' => $status->label(),
            'status_summary' => $this->statusSummary($invoice, $status),
            'billing_period_start_display' => $invoice->billing_period_start?->locale(app()->getLocale())->isoFormat('ll') ?? '—',
            'billing_period_end_display' => $invoice->billing_period_end?->locale(app()->getLocale())->isoFormat('ll') ?? '—',
            'due_date_display' => $invoice->due_date?->locale(app()->getLocale())->isoFormat('ll') ?? '—',
            'property_name' => (string) ($invoice->property?->name ?? '—'),
            'building_name' => (string) ($invoice->property?->building?->name ?? ''),
            'tenant_name' => (string) ($invoice->tenant?->name ?? '—'),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount,
            'total_amount_display' => EuMoneyFormatter::format($totalAmount, $currency),
            'paid_amount_display' => EuMoneyFormatter::format($paidAmount, $currency),
            'outstanding_amount_display' => EuMoneyFormatter::format($outstandingAmount, $currency),
            'subtotal_display' => EuMoneyFormatter::format($subtotal, $currency),
            'items' => $items,
            'payments' => $invoice->payments
                ->map(fn (InvoicePayment $payment): array => [
                    'method_label' => (string) ($payment->method?->label() ?? __('dashboard.not_available')),
                    'paid_at_display' => $payment->paid_at?->locale(app()->getLocale())->isoFormat('LLL') ?? '—',
                    'amount_display' => EuMoneyFormatter::format($this->calculator->money($payment->amount ?? '0'), $currency),
                    'reference' => (string) ($payment->reference ?? ''),
                    'notes' => (string) ($payment->notes ?? ''),
                ])
                ->all(),
        ];
    }

    public function lineItemSummary(Invoice $invoice): string
    {
        $presentation = $this->present($invoice);

        if ($presentation['items'] === []) {
            return __('admin.invoices.pdf.empty_items');
        }

        return collect($presentation['items'])
            ->map(fn (array $item): string => trim(implode(' · ', array_filter([
                $item['description'],
                trim($item['quantity'].($item['unit'] !== '' ? ' '.$item['unit'] : '')),
                EuMoneyFormatter::format($item['total'], (string) $presentation['currency']),
            ]))))
            ->implode(PHP_EOL);
    }

    /**
     * @return array<int, array{
     *     description: string,
     *     period: string,
     *     quantity: string,
     *     unit: string,
     *     unit_price: string,
     *     unit_price_display: string,
     *     total: string,
     *     total_display: string,
     *     is_adjustment: bool
     * }>
     */
    private function presentItems(Invoice $invoice, string $currency): array
    {
        return array_values(array_map(function (mixed $item) use ($currency): array {
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
                'period' => filled($resolvedItem['period'] ?? null)
                    ? (string) $resolvedItem['period']
                    : '—',
                'quantity' => $quantity,
                'unit' => (string) ($resolvedItem['unit'] ?? ''),
                'unit_price' => $unitPrice,
                'unit_price_display' => EuMoneyFormatter::format($unitPrice, $currency),
                'total' => $total,
                'total_display' => EuMoneyFormatter::format($total, $currency),
                'is_adjustment' => (bool) ($resolvedItem['is_adjustment'] ?? false),
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

        if ($invoice->relationLoaded('invoiceItems') && $invoice->invoiceItems->isNotEmpty()) {
            return $invoice->invoiceItems
                ->map(fn ($item): array => [
                    'description' => (string) $item->description,
                    'period' => null,
                    'quantity' => (string) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (string) $item->unit_price,
                    'rate' => (string) $item->unit_price,
                    'total' => (string) $item->total,
                    'consumption' => (string) $item->quantity,
                    'is_adjustment' => false,
                    'meter_reading_snapshot' => $item->meter_reading_snapshot,
                ])
                ->all();
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
}
