<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Models\Invoice;
use App\Models\Organization;
use Carbon\CarbonInterface;

final readonly class OrganizationFinancialSnapshot
{
    /**
     * @param  list<array{label: string, value: string}>  $cards
     */
    public function __construct(
        public string $mrrDisplay,
        public string $outstandingDisplay,
        public string $overdueDisplay,
        public string $collectedThisMonthDisplay,
        public string $avgDaysToPayLabel,
    ) {}

    public static function fromOrganization(
        Organization $organization,
        OrganizationMrrResolver $mrrResolver,
    ): self {
        $outstandingInvoices = $organization->invoices()
            ->select(self::invoiceColumns())
            ->outstanding()
            ->get();

        $paidInvoices = $organization->invoices()
            ->select(self::invoiceColumns())
            ->whereNotNull('paid_at')
            ->get();

        $currency = self::resolveCurrency($organization, $outstandingInvoices->first(), $paidInvoices->first());
        $outstandingTotal = (float) $outstandingInvoices->sum(
            fn (Invoice $invoice): float => $invoice->outstanding_balance,
        );
        $overdueTotal = (float) $outstandingInvoices->sum(
            fn (Invoice $invoice): float => $invoice->isOverdue() ? $invoice->outstanding_balance : 0.0,
        );
        $collectedThisMonth = (float) $paidInvoices
            ->filter(fn (Invoice $invoice): bool => self::paidThisMonth($invoice->paid_at))
            ->sum(fn (Invoice $invoice): float => $invoice->normalized_paid_amount);
        $averageDaysToPay = $paidInvoices
            ->map(fn (Invoice $invoice): ?int => self::daysToPayFor($invoice))
            ->filter(static fn (?int $days): bool => $days !== null)
            ->avg();

        return new self(
            mrrDisplay: $mrrResolver->displayFor($organization),
            outstandingDisplay: self::formatMoney($outstandingTotal, $currency),
            overdueDisplay: self::formatMoney($overdueTotal, $currency),
            collectedThisMonthDisplay: self::formatMoney($collectedThisMonth, $currency),
            avgDaysToPayLabel: $averageDaysToPay === null
                ? __('superadmin.organizations.overview.placeholders.not_available')
                : __('superadmin.organizations.overview.days_to_pay', ['days' => (int) round($averageDaysToPay)]),
        );
    }

    /**
     * @return list<string>
     */
    private static function invoiceColumns(): array
    {
        return [
            'id',
            'organization_id',
            'status',
            'currency',
            'total_amount',
            'amount_paid',
            'paid_amount',
            'finalized_at',
            'paid_at',
            'created_at',
            'due_date',
            'billing_period_end',
        ];
    }

    private static function resolveCurrency(
        Organization $organization,
        ?Invoice $outstandingInvoice,
        ?Invoice $paidInvoice,
    ): string {
        return data_get($organization, 'currentSubscription.latestPayment.currency')
            ?? $outstandingInvoice?->currency
            ?? $paidInvoice?->currency
            ?? 'EUR';
    }

    private static function paidThisMonth(?CarbonInterface $paidAt): bool
    {
        if (! $paidAt instanceof CarbonInterface) {
            return false;
        }

        return $paidAt->between(now()->startOfMonth(), now()->endOfMonth());
    }

    private static function daysToPayFor(Invoice $invoice): ?int
    {
        if (! $invoice->paid_at instanceof CarbonInterface) {
            return null;
        }

        $referenceDate = $invoice->finalized_at ?? $invoice->created_at;

        if (! $referenceDate instanceof CarbonInterface) {
            return null;
        }

        return max(0, (int) $referenceDate->diffInDays($invoice->paid_at));
    }

    private static function formatMoney(float $amount, string $currency): string
    {
        return sprintf('%s %s', $currency, number_format($amount, 2, '.', ''));
    }
}
