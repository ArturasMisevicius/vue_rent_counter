<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\InvoicePayment;
use App\Services\Billing\InvoicePresentationService;

final class InvoiceViewPresenter
{
    public function __construct(
        private readonly InvoicePresentationService $invoicePresentationService,
    ) {}

    /**
     * @return array{
     *     presentation: array<string, mixed>,
     *     subtitle: string,
     *     summary: array<string, array<int, array{label: string, value: string, badge: bool, color: string|null}>>,
     *     charge_rows: array<int, array{description: string, period: string, quantity: string, rate: string, total: string, is_adjustment: bool}>,
     *     subtotal_display: string,
     *     adjustments_display: string|null,
     *     total_display: string,
     *     payment_history: array<int, array{date: string, amount: string, reference: string}>,
     *     email_history: array<int, array{date: string, recipient_email: string}>,
     *     draft_notice: string|null,
     *     overdue_notice: string|null,
     *     payment_history_empty: string,
     *     email_history_empty: string
     * }
     */
    public function present(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'tenant:id,organization_id,name,email',
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at,notes',
            'emailLogs:id,invoice_id,organization_id,sent_by_user_id,recipient_email,subject,status,sent_at,personal_message',
            'reminderLogs:id,invoice_id,organization_id,sent_by_user_id,recipient_email,channel,sent_at,notes',
        ]);

        $presentation = $this->invoicePresentationService->present($invoice);
        $chargeRows = $this->chargeRows($presentation, $invoice);
        $items = is_array($presentation['items'] ?? null) ? $presentation['items'] : [];

        return [
            'presentation' => $presentation,
            'subtitle' => $this->subtitle($invoice),
            'summary' => $this->summary($invoice, $presentation),
            'charge_rows' => $chargeRows,
            'subtotal_display' => $this->formatCurrency((string) $invoice->currency, $this->subtotal($items)),
            'adjustments_display' => $this->adjustmentsDisplay($invoice, $items),
            'total_display' => (string) ($presentation['total_amount_display'] ?? '—'),
            'payment_history' => $this->paymentHistory($invoice),
            'email_history' => $this->emailHistory($invoice),
            'draft_notice' => $this->draftNotice($invoice),
            'overdue_notice' => $this->overdueNotice($invoice),
            'payment_history_empty' => __('admin.invoices.empty.payment_history'),
            'email_history_empty' => __('admin.invoices.empty.email_history'),
        ];
    }

    private function subtitle(Invoice $invoice): string
    {
        $tenant = (string) ($invoice->tenant?->name ?? __('admin.invoices.empty.tenant'));
        $period = InvoiceTablePresenter::billingPeriod($invoice);

        return trim($tenant.' · '.$period, ' ·');
    }

    /**
     * @param  array<string, mixed>  $presentation
     * @return array<string, array<int, array{label: string, value: string, badge: bool, color: string|null}>>
     */
    private function summary(Invoice $invoice, array $presentation): array
    {
        $status = $invoice->effectiveStatus();

        return [
            'left' => [
                ['label' => __('admin.invoices.fields.invoice_number'), 'value' => (string) ($presentation['invoice_number'] ?? '—'), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.tenant'), 'value' => (string) ($presentation['tenant_name'] ?? '—'), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.property'), 'value' => (string) ($presentation['property_name'] ?? '—'), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.billing_period_start'), 'value' => (string) ($presentation['billing_period_start_display'] ?? '—'), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.billing_period_end'), 'value' => (string) ($presentation['billing_period_end_display'] ?? '—'), 'badge' => false, 'color' => null],
            ],
            'right' => [
                ['label' => __('admin.invoices.fields.status'), 'value' => $status->label(), 'badge' => true, 'color' => InvoiceTablePresenter::statusColor($invoice)],
                ['label' => __('admin.invoices.fields.issued_date'), 'value' => InvoiceTablePresenter::issuedDate($invoice), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.finalized_date'), 'value' => $this->formatDateTime($invoice->finalized_at), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.paid_date'), 'value' => InvoiceTablePresenter::paidDate($invoice), 'badge' => false, 'color' => null],
                ['label' => __('admin.invoices.fields.payment_reference'), 'value' => filled($invoice->payment_reference) ? (string) $invoice->payment_reference : '—', 'badge' => false, 'color' => null],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $presentation
     * @return array<int, array{description: string, period: string, quantity: string, rate: string, total: string, is_adjustment: bool}>
     */
    private function chargeRows(array $presentation, Invoice $invoice): array
    {
        $currency = (string) ($presentation['currency'] ?? $invoice->currency ?? '');
        $fallbackPeriod = InvoiceTablePresenter::billingPeriod($invoice);

        return collect($presentation['items'] ?? [])
            ->map(function (array $item) use ($currency, $fallbackPeriod): array {
                $unit = trim((string) ($item['unit'] ?? ''));
                $quantity = trim((string) ($item['quantity'] ?? ''));
                $rate = EuMoneyFormatter::format($item['unit_price'] ?? 0, $currency);

                if ($unit !== '') {
                    $rate .= ' / '.$unit;
                }

                return [
                    'description' => (string) ($item['description'] ?? '—'),
                    'period' => (string) ($item['period'] ?? $fallbackPeriod ?: '—'),
                    'quantity' => trim($quantity.($unit !== '' ? ' '.$unit : '')),
                    'rate' => $rate,
                    'total' => EuMoneyFormatter::format($item['total'] ?? 0, $currency),
                    'is_adjustment' => (bool) ($item['is_adjustment'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function adjustmentsDisplay(Invoice $invoice, array $items): ?string
    {
        $total = array_sum(array_map(
            fn (array $item): float => ($item['is_adjustment'] ?? false) ? (float) ($item['total'] ?? 0) : 0.0,
            $items,
        ));

        if ((float) $total === 0.0) {
            return null;
        }

        return $this->formatCurrency((string) $invoice->currency, $total);
    }

    /**
     * @return array<int, array{date: string, amount: string, reference: string}>
     */
    private function paymentHistory(Invoice $invoice): array
    {
        return $invoice->payments
            ->sortByDesc(fn (InvoicePayment $payment): int => $payment->paid_at?->getTimestamp() ?? 0)
            ->map(fn (InvoicePayment $payment): array => [
                'date' => $this->formatDateTime($payment->paid_at),
                'amount' => $this->formatCurrency((string) $invoice->currency, (float) $payment->amount),
                'reference' => filled($payment->reference) ? (string) $payment->reference : '—',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{date: string, recipient_email: string}>
     */
    private function emailHistory(Invoice $invoice): array
    {
        return $invoice->emailLogs
            ->sortByDesc(fn (InvoiceEmailLog $log): int => $log->sent_at?->getTimestamp() ?? 0)
            ->map(fn (InvoiceEmailLog $log): array => [
                'date' => $this->formatDateTime($log->sent_at),
                'recipient_email' => (string) $log->recipient_email,
            ])
            ->values()
            ->all();
    }

    private function draftNotice(Invoice $invoice): ?string
    {
        return $invoice->effectiveStatus() === InvoiceStatus::DRAFT
            ? __('admin.invoices.messages.draft_notice')
            : null;
    }

    private function overdueNotice(Invoice $invoice): ?string
    {
        if ($invoice->effectiveStatus() !== InvoiceStatus::OVERDUE) {
            return null;
        }

        $lastReminder = $invoice->last_reminder_sent_at
            ? __('admin.invoices.messages.overdue_notice_with_reminder', [
                'date' => $this->formatDateTime($invoice->last_reminder_sent_at),
            ])
            : __('admin.invoices.messages.overdue_notice_without_reminder');

        return __('admin.invoices.messages.overdue_notice_prefix').' '.$lastReminder;
    }

    private function formatDateTime(mixed $value): string
    {
        if (! is_object($value) || ! method_exists($value, 'format')) {
            return '—';
        }

        return $value->locale(app()->getLocale())->isoFormat('LLL');
    }

    private function formatCurrency(string $currency, float $amount): string
    {
        return EuMoneyFormatter::format($amount, $currency);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function subtotal(array $items): float
    {
        return array_sum(array_map(
            fn (array $item): float => ($item['is_adjustment'] ?? false) ? 0.0 : (float) ($item['total'] ?? 0),
            $items,
        ));
    }
}
