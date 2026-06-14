<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Billing\InvoiceCalculationRows;
use App\Filament\Support\Billing\InvoiceContentLocalizer;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Invoice;
use App\Models\InvoicePayment;

final class InvoicePresentationService
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
        private readonly InvoiceContentLocalizer $contentLocalizer,
        private readonly InvoiceCalculationRows $calculationRows,
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
            'payments:id,invoice_id,organization_id,tenant_id,property_id,amount,currency,method,payment_method,status,payment_date,reference,transaction_id,paid_at,confirmed_at,rejected_at,rejection_reason,voided_at,void_reason,tenant_comment,created_at',
            'payments.attachments:id,organization_id,attachable_type,attachable_id,uploaded_by_user_id,filename,original_filename,mime_type,size,disk,path,document_type,tenant_visible,created_at',
            'invoiceItems:id,invoice_id,source_type,source_id,title,description,description_for_tenant,quantity,unit,unit_price,subtotal,tax_amount,discount_amount,total,currency,formula_label,calculation_snapshot,tenant_visible,sort_order,meter_reading_snapshot,service_snapshot,tariff_snapshot,provider_snapshot',
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
            'billing_period_start_display' => LocalizedDateFormatter::date($invoice->billing_period_start),
            'billing_period_end_display' => LocalizedDateFormatter::date($invoice->billing_period_end),
            'due_date_display' => LocalizedDateFormatter::date($invoice->due_date),
            'property_name' => (string) ($invoice->property?->displayName() ?? '—'),
            'building_name' => (string) ($invoice->property?->building?->displayName() ?? ''),
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
                    'method_label' => $payment->methodLabel(),
                    'status' => $payment->status?->value ?? '',
                    'status_label' => $payment->statusLabel(),
                    'paid_at_display' => LocalizedDateFormatter::dateTime($payment->paid_at ?? $payment->created_at),
                    'payment_date_display' => LocalizedDateFormatter::date($payment->payment_date),
                    'amount_display' => EuMoneyFormatter::format($this->calculator->money($payment->amount ?? '0'), $currency),
                    'reference' => (string) ($payment->reference ?? ''),
                    'rejection_reason' => (string) ($payment->rejection_reason ?? ''),
                    'attachments' => $payment->attachments
                        ->filter(fn ($attachment): bool => (bool) $attachment->tenant_visible)
                        ->map(fn ($attachment): array => [
                            'name' => (string) ($attachment->original_filename ?: $attachment->filename),
                            'url' => route('tenant.attachments.show', ['attachment' => $attachment]),
                        ])
                        ->values()
                        ->all(),
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
        return array_values(array_map(function (array $item) use ($currency): array {
            $quantity = $this->calculator->quantity($item['quantity'] ?? 1);
            $unitPrice = $this->calculator->rate(
                $item['unit_price']
                ?? $item['rate']
                ?? $item['amount']
                ?? $item['total']
                ?? '0',
            );
            $total = $this->calculator->money(
                $item['total']
                ?? $item['amount']
                ?? '0',
            );
            $itemCurrency = (string) ($item['currency'] ?? $currency);

            return [
                'description' => $this->contentLocalizer->lineItemDescription(
                    (string) ($item['description_for_tenant'] ?? $item['description'] ?? ''),
                ),
                'period' => filled($item['period'] ?? null)
                    ? (string) $item['period']
                    : '—',
                'quantity' => $quantity,
                'unit' => $this->contentLocalizer->unit((string) ($item['unit'] ?? '')),
                'unit_price' => $unitPrice,
                'unit_price_display' => EuMoneyFormatter::format($unitPrice, $itemCurrency),
                'subtotal' => $this->calculator->money($item['subtotal'] ?? $total),
                'tax_amount' => $this->calculator->money($item['tax_amount'] ?? '0'),
                'discount_amount' => $this->calculator->money($item['discount_amount'] ?? '0'),
                'total' => $total,
                'total_display' => EuMoneyFormatter::format($total, $itemCurrency),
                'is_adjustment' => (bool) ($item['is_adjustment'] ?? false),
                'source_type' => (string) ($item['source_type'] ?? ''),
                'source_label' => (string) ($item['source_label'] ?? ''),
                'formula_label' => (string) ($item['formula_label'] ?? ''),
                'meter_reading_snapshot' => is_array($item['meter_reading_snapshot'] ?? null)
                    ? $item['meter_reading_snapshot']
                    : null,
            ];
        }, $this->calculationRows->forInvoice($invoice, tenantVisibleOnly: true)));
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
