<?php

namespace App\Filament\Support\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class InvoiceTablePresenter
{
    public static function billingPeriod(Invoice $invoice): string
    {
        $start = $invoice->billing_period_start?->format('M j, Y');
        $end = $invoice->billing_period_end?->format('M j, Y');

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
        return sprintf('%s %s', $invoice->currency, number_format((float) $invoice->total_amount, 2));
    }

    public static function issuedDate(Invoice $invoice): string
    {
        return $invoice->finalized_at?->format('F j, Y g:i A')
            ?? __('admin.invoices.empty.issued_date');
    }

    public static function paidDate(Invoice $invoice): string
    {
        return $invoice->paid_at?->format('F j, Y g:i A') ?? '—';
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
