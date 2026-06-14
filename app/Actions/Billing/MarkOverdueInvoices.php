<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\InvoicePaymentStatus;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class MarkOverdueInvoices
{
    public function __construct(
        private readonly RecalculateInvoicePaymentStatus $recalculateInvoicePaymentStatus,
    ) {}

    public function handle(CarbonInterface|string|null $asOf = null, ?User $actor = null): int
    {
        $today = $asOf instanceof CarbonInterface
            ? CarbonImmutable::instance($asOf)->startOfDay()
            : CarbonImmutable::parse((string) ($asOf ?: now()->toDateString()))->startOfDay();
        $marked = 0;

        Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'payment_status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'balance_amount',
                'due_date',
                'paid_at',
                'payment_reference',
                'overdue_at',
            ])
            ->withoutWriteOff()
            ->whereIn('status', [
                InvoiceStatus::FINALIZED,
                InvoiceStatus::PARTIALLY_PAID,
                InvoiceStatus::OVERDUE,
            ])
            ->where(function (Builder $query) use ($today): void {
                $query
                    ->where(function (Builder $dueDateQuery) use ($today): void {
                        $dueDateQuery
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', $today->toDateString());
                    })
                    ->orWhere(function (Builder $fallbackQuery) use ($today): void {
                        $fallbackQuery
                            ->whereNull('due_date')
                            ->whereDate('billing_period_end', '<', $today->toDateString());
                    });
            })
            ->chunkById(100, function ($invoices) use (&$marked, $actor, $today): void {
                foreach ($invoices as $invoice) {
                    $beforeStatus = $invoice->payment_status instanceof InvoicePaymentStatus
                        ? $invoice->payment_status
                        : InvoicePaymentStatus::tryFrom((string) $invoice->payment_status);
                    $updated = $this->recalculateInvoicePaymentStatus->handle(
                        $invoice,
                        $actor,
                        'invoice.marked_overdue',
                        $today,
                    );

                    if ($beforeStatus !== InvoicePaymentStatus::OVERDUE
                        && $updated->payment_status === InvoicePaymentStatus::OVERDUE) {
                        $marked++;
                    }
                }
            });

        return $marked;
    }
}
