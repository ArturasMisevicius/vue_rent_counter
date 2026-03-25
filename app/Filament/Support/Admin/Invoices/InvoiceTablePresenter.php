<?php

namespace App\Filament\Support\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class InvoiceTablePresenter
{
    public static function billingPeriod(Invoice $invoice): string
    {
        $start = $invoice->billing_period_start?->locale(app()->getLocale())->isoFormat('ll');
        $end = $invoice->billing_period_end?->locale(app()->getLocale())->isoFormat('ll');

        if ($start === null && $end === null) {
            return '—';
        }

        if ($start === null) {
            return (string) $end;
        }

        if ($end === null) {
            return (string) $start;
        }

        return "{$start} - {$end}";
    }

    public static function amount(Invoice $invoice): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency((float) $invoice->total_amount, $invoice->currency);
    }

    public static function issuedDate(Invoice $invoice): string
    {
        return $invoice->finalized_at?->locale(app()->getLocale())->isoFormat('LLL')
            ?? __('admin.invoices.empty.issued_date');
    }

    public static function paidDate(Invoice $invoice): string
    {
        return $invoice->paid_at?->locale(app()->getLocale())->isoFormat('LLL') ?? '—';
    }

    public static function status(Invoice $invoice): InvoiceStatus
    {
        return $invoice->effectiveStatus();
    }

    public static function statusColor(Invoice $invoice): string
    {
        return match (self::status($invoice)) {
            InvoiceStatus::DRAFT => 'gray',
            InvoiceStatus::FINALIZED => 'info',
            InvoiceStatus::PARTIALLY_PAID => 'warning',
            InvoiceStatus::PAID => 'success',
            InvoiceStatus::OVERDUE => 'danger',
            InvoiceStatus::VOID => 'gray',
        };
    }

    public static function tenantDescription(Invoice $invoice): string
    {
        return $invoice->property?->name ?? '—';
    }
}
