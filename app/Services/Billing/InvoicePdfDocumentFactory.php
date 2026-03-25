<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Filament\Support\Tenant\Portal\PaymentInstructionsResolver;
use App\Models\Invoice;
use App\Models\InvoicePayment;

final class InvoicePdfDocumentFactory
{
    public function __construct(
        private readonly InvoicePresentationService $invoicePresentationService,
        private readonly PaymentInstructionsResolver $paymentInstructionsResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function make(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'tenant:id,organization_id,name,email',
            'tenant.organization:id,name',
            'tenant.organization.settings:id,organization_id,billing_contact_name,billing_contact_email,billing_contact_phone,payment_instructions,invoice_footer',
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at,notes',
            'invoiceItems:id,invoice_id,description,quantity,unit,unit_price,total,meter_reading_snapshot',
        ]);

        $presentation = $this->invoicePresentationService->present($invoice);
        $paymentGuidance = $this->paymentInstructionsResolver->resolve(
            $invoice->tenant?->organization?->settings,
        );

        $items = array_map(function (array $item) use ($presentation): array {
            $quantity = trim((string) ($item['quantity'] ?? '').(filled($item['unit'] ?? null) ? ' '.(string) $item['unit'] : ''));

            return [
                'description' => (string) ($item['description'] ?? ''),
                'description_lines' => $this->wrapText((string) ($item['description'] ?? ''), 40),
                'quantity' => $quantity === '' ? '—' : $quantity,
                'period' => (string) ($item['period'] ?? '—'),
                'unit_price' => $this->formatCurrency(
                    (string) $presentation['currency'],
                    (string) ($item['unit_price_display'] ?? '0.00'),
                ),
                'total' => $this->formatCurrency(
                    (string) $presentation['currency'],
                    (string) ($item['total_display'] ?? '0.00'),
                ),
                'is_adjustment' => (bool) ($item['is_adjustment'] ?? false),
            ];
        }, $presentation['items']);

        return [
            'title' => __('admin.invoices.pdf.title', ['number' => $presentation['invoice_number']]),
            'subtitle' => __('tenant.pages.invoices.page_heading'),
            'locale' => app()->getLocale(),
            'direction' => in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur'], true) ? 'rtl' : 'ltr',
            'issued_on' => $invoice->finalized_at?->locale(app()->getLocale())->isoFormat('ll')
                ?? $invoice->created_at?->locale(app()->getLocale())->isoFormat('ll')
                ?? now()->locale(app()->getLocale())->isoFormat('ll'),
            'summary' => [
                ['label' => __('admin.invoices.fields.invoice_number'), 'value' => $presentation['invoice_number']],
                ['label' => __('admin.invoices.fields.tenant'), 'value' => (string) ($invoice->tenant?->name ?? '—')],
                ['label' => __('admin.invoices.fields.property'), 'value' => (string) ($invoice->property?->name ?? '—')],
                ['label' => __('admin.invoices.fields.building'), 'value' => (string) ($invoice->property?->building?->name ?? '—')],
                ['label' => __('admin.invoices.fields.status'), 'value' => $presentation['status_label']],
                ['label' => __('admin.invoices.fields.due_date'), 'value' => $presentation['due_date_display']],
            ],
            'property_lines' => array_values(array_filter([
                (string) ($invoice->property?->name ?? '—'),
                filled($invoice->property?->unit_number) ? __('admin.invoices.fields.property').': '.$invoice->property->unit_number : null,
                $this->buildingAddress($invoice),
            ])),
            'payment_guidance_lines' => $this->wrapText(
                (string) ($paymentGuidance['content'] ?? __('tenant.messages.payment_guidance_unavailable')),
                62,
            ),
            'payment_contact_lines' => array_values(array_filter([
                $paymentGuidance['contact_name'],
                $paymentGuidance['contact_email'],
                $paymentGuidance['contact_phone'],
            ])),
            'totals' => [
                ['label' => __('admin.invoices.fields.total_amount'), 'value' => $presentation['total_amount_display']],
                ['label' => __('admin.invoices.fields.amount_paid'), 'value' => $presentation['paid_amount_display']],
                ['label' => __('admin.invoices.status_summaries.outstanding'), 'value' => $presentation['outstanding_amount_display']],
            ],
            'items' => $items,
            'pages' => $this->paginateItems($items),
            'payments' => array_map(
                fn (InvoicePayment $payment): array => [
                    'label' => (string) ($payment->method?->label() ?? __('dashboard.not_available')),
                    'meta' => trim(implode(' · ', array_filter([
                        $payment->paid_at?->locale(app()->getLocale())->isoFormat('ll'),
                        $payment->reference,
                    ]))),
                    'amount' => $this->formatCurrency(
                        (string) $presentation['currency'],
                        (string) ($payment->amount ?? '0.00'),
                    ),
                ],
                $invoice->payments->all(),
            ),
            'empty_items_label' => __('admin.invoices.pdf.empty_items'),
            'table_labels' => [
                'description' => __('admin.invoices.fields.description'),
                'quantity' => __('admin.invoices.fields.quantity'),
                'unit_price' => __('admin.invoices.fields.unit_price'),
                'total' => __('admin.invoices.fields.total'),
            ],
            'payment_labels' => [
                'guidance' => __('tenant.pages.invoices.payment_guidance'),
                'how_to_pay' => __('tenant.pages.invoices.how_to_pay'),
                'contact' => __('tenant.shell.billing_contact'),
                'period' => __('tenant.pages.invoices.period', [
                    'start' => $presentation['billing_period_start_display'],
                    'end' => $presentation['billing_period_end_display'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{index: int, items: array<int, array<string, mixed>>, is_first: bool, is_last: bool}>
     */
    private function paginateItems(array $items): array
    {
        if ($items === []) {
            return [[
                'index' => 0,
                'items' => [],
                'is_first' => true,
                'is_last' => true,
            ]];
        }

        $firstPageCount = 8;
        $followUpPageCount = 12;
        $pages = [];
        $remainingItems = $items;
        $pageIndex = 0;

        while ($remainingItems !== []) {
            $chunkSize = $pageIndex === 0 ? $firstPageCount : $followUpPageCount;
            $pages[] = [
                'index' => $pageIndex,
                'items' => array_splice($remainingItems, 0, $chunkSize),
                'is_first' => $pageIndex === 0,
                'is_last' => $remainingItems === [],
            ];
            $pageIndex++;
        }

        return $pages;
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, int $lineLength): array
    {
        $normalized = trim($text);

        if ($normalized === '') {
            return ['—'];
        }

        $words = preg_split('/\s+/u', $normalized) ?: [];
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $candidate = $currentLine === '' ? $word : $currentLine.' '.$word;

            if (mb_strlen($candidate) <= $lineLength) {
                $currentLine = $candidate;

                continue;
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }

            $currentLine = $word;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines === [] ? ['—'] : $lines;
    }

    private function buildingAddress(Invoice $invoice): string
    {
        $building = $invoice->property?->building;

        if ($building === null) {
            return '';
        }

        return collect([
            $building->address_line_1,
            $building->address_line_2,
            $building->city,
            $building->postal_code,
            $building->country_code,
        ])->filter(fn (?string $value): bool => filled($value))->implode(', ');
    }

    private function formatCurrency(string $currency, string $amount): string
    {
        return trim($currency.' '.$amount);
    }
}
