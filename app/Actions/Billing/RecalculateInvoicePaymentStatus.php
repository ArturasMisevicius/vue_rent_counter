<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\AuditLogAction;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RecalculateInvoicePaymentStatus
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        Invoice $invoice,
        ?User $actor = null,
        string $mutation = 'invoice.payment_status_recalculated',
        ?CarbonInterface $asOf = null,
    ): Invoice {
        return DB::transaction(function () use ($invoice, $actor, $mutation, $asOf): Invoice {
            $invoice->refresh();
            $before = $this->invoiceSnapshot($invoice);
            $confirmedPaidAmount = (float) InvoicePayment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', PaymentStatus::CONFIRMED)
                ->sum('amount');
            $totalAmount = (float) $invoice->total_amount;
            $balanceAmount = max(0, $totalAmount - $confirmedPaidAmount);
            $paymentStatus = $this->paymentStatus($invoice, $totalAmount, $confirmedPaidAmount, $balanceAmount, $asOf);
            $invoiceStatus = $this->invoiceStatus($invoice, $paymentStatus);
            $latestConfirmedPayment = InvoicePayment::query()
                ->select(['id', 'invoice_id', 'reference', 'paid_at', 'confirmed_at'])
                ->where('invoice_id', $invoice->id)
                ->where('status', PaymentStatus::CONFIRMED)
                ->latest('confirmed_at')
                ->latest('id')
                ->first();

            $invoice->forceFill([
                'amount_paid' => round($confirmedPaidAmount, 2),
                'paid_amount' => round($confirmedPaidAmount, 2),
                'balance_amount' => round($balanceAmount, 2),
                'payment_status' => $paymentStatus,
                'status' => $invoiceStatus,
                'paid_at' => in_array($paymentStatus, [
                    InvoicePaymentStatus::PAID,
                    InvoicePaymentStatus::OVERPAID,
                ], true) ? ($latestConfirmedPayment?->paid_at ?? now()) : null,
                'payment_reference' => filled($invoice->payment_reference)
                    ? $invoice->payment_reference
                    : $latestConfirmedPayment?->reference,
            ]);

            if ($paymentStatus === InvoicePaymentStatus::OVERDUE && $invoice->overdue_at === null) {
                $invoice->overdue_at = now();
            }

            $invoice->save();

            $freshInvoice = $invoice->fresh(['payments']);
            $after = $this->invoiceSnapshot($freshInvoice);

            if ($before !== $after) {
                $this->auditLogger->record(
                    AuditLogAction::UPDATED,
                    $freshInvoice,
                    [
                        'workspace' => $this->workspaceContext($freshInvoice),
                        'context' => [
                            'mutation' => $mutation,
                        ],
                        'before' => $before,
                        'after' => $after,
                    ],
                    $actor?->id,
                    'Invoice payment status recalculated',
                );
            }

            return $freshInvoice;
        });
    }

    private function paymentStatus(
        Invoice $invoice,
        float $totalAmount,
        float $confirmedPaidAmount,
        float $balanceAmount,
        ?CarbonInterface $asOf,
    ): InvoicePaymentStatus {
        if ($invoice->status === InvoiceStatus::VOID) {
            return InvoicePaymentStatus::VOIDED;
        }

        if ($confirmedPaidAmount > $totalAmount && $totalAmount > 0) {
            return InvoicePaymentStatus::OVERPAID;
        }

        if ($totalAmount > 0 && $balanceAmount <= 0 && $confirmedPaidAmount >= $totalAmount) {
            return InvoicePaymentStatus::PAID;
        }

        if ($balanceAmount > 0 && $this->isPaymentOverdue($invoice, $asOf)) {
            return InvoicePaymentStatus::OVERDUE;
        }

        if ($confirmedPaidAmount > 0) {
            return InvoicePaymentStatus::PARTIALLY_PAID;
        }

        return InvoicePaymentStatus::UNPAID;
    }

    private function isPaymentOverdue(Invoice $invoice, ?CarbonInterface $asOf): bool
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status);

        if ($status === InvoiceStatus::DRAFT || $status === InvoiceStatus::VOID) {
            return false;
        }

        $referenceDate = $invoice->overdueReferenceDate();

        if ($referenceDate === null) {
            return false;
        }

        $comparisonDate = $asOf instanceof CarbonInterface
            ? $asOf->copy()->startOfDay()
            : now()->startOfDay();

        return $referenceDate->lt($comparisonDate);
    }

    private function invoiceStatus(Invoice $invoice, InvoicePaymentStatus $paymentStatus): InvoiceStatus
    {
        $currentStatus = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status) ?? InvoiceStatus::DRAFT;

        if ($currentStatus === InvoiceStatus::DRAFT || $currentStatus === InvoiceStatus::VOID) {
            return $currentStatus;
        }

        return match ($paymentStatus) {
            InvoicePaymentStatus::PAID,
            InvoicePaymentStatus::OVERPAID => InvoiceStatus::PAID,
            InvoicePaymentStatus::PARTIALLY_PAID => InvoiceStatus::PARTIALLY_PAID,
            InvoicePaymentStatus::OVERDUE => InvoiceStatus::OVERDUE,
            InvoicePaymentStatus::VOIDED => InvoiceStatus::VOID,
            default => InvoiceStatus::FINALIZED,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceSnapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status instanceof InvoiceStatus ? $invoice->status->value : $invoice->status,
            'payment_status' => $invoice->payment_status instanceof InvoicePaymentStatus
                ? $invoice->payment_status->value
                : $invoice->payment_status,
            'total_amount' => $this->numeric($invoice->total_amount),
            'amount_paid' => $this->numeric($invoice->amount_paid),
            'paid_amount' => $this->numeric($invoice->paid_amount),
            'balance_amount' => $this->numeric($invoice->balance_amount),
            'payment_reference' => $invoice->payment_reference,
            'paid_at' => $invoice->paid_at?->toISOString(),
            'overdue_at' => $invoice->overdue_at?->toISOString(),
        ];
    }

    /**
     * @return array{organization_id: int, property_id: int|null, tenant_user_id: int|null}
     */
    private function workspaceContext(Invoice $invoice): array
    {
        return [
            'organization_id' => $invoice->organization_id,
            'property_id' => $invoice->property_id,
            'tenant_user_id' => $invoice->tenant_user_id,
        ];
    }

    private function numeric(string|int|float|null $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numericValue = (float) $value;

        return (float) (int) $numericValue === $numericValue ? (int) $numericValue : $numericValue;
    }
}
