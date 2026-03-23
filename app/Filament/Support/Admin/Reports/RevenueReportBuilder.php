<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class RevenueReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{building_id: int|null, property_id: int|null, tenant_id: int|null, status_filter: string|null}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, mixed>>,
     *     empty_state: string
     * }
     */
    public function build(int $organizationId, CarbonInterface $startDate, CarbonInterface $endDate, array $filters): array
    {
        $invoices = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'billing_period_end',
            ])
            ->forOrganization($organizationId)
            ->whereDate('billing_period_end', '>=', $startDate->toDateString())
            ->whereDate('billing_period_end', '<=', $endDate->toDateString())
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'property',
                    fn ($propertyQuery) => $propertyQuery->where('building_id', (int) $filters['building_id']),
                ),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->where('property_id', (int) $filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->where('tenant_user_id', (int) $filters['tenant_id']),
            )
            ->when(
                filled($filters['status_filter'] ?? null) && $filters['status_filter'] !== 'all',
                fn ($query) => $query->where('status', (string) $filters['status_filter']),
            )
            ->get()
            ->filter(fn (Invoice $invoice): bool => filled($invoice->billing_period_end))
            ->filter(function (Invoice $invoice) use ($startDate, $endDate): bool {
                $periodEnd = $invoice->billing_period_end;

                if ($periodEnd === null) {
                    return false;
                }

                return $periodEnd->betweenIncluded($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay());
            });

        /** @var Collection<string, array{month: string, raw_total: float, raw_paid: float, raw_outstanding: float, invoice_count: int, currency: string}> $monthlyBuckets */
        $monthlyBuckets = collect();

        foreach ($invoices as $invoice) {
            $month = $this->monthKey($invoice->billing_period_end);

            if ($month === '') {
                continue;
            }

            $normalizedPaid = $this->normalizedPaidAmount($invoice->amount_paid, $invoice->paid_amount);
            $rawTotal = (float) $invoice->total_amount;
            $rawOutstanding = $this->outstandingAmount($rawTotal, $normalizedPaid);

            if (! $monthlyBuckets->has($month)) {
                $monthlyBuckets->put($month, [
                    'month' => $month,
                    'raw_total' => 0.0,
                    'raw_paid' => 0.0,
                    'raw_outstanding' => 0.0,
                    'invoice_count' => 0,
                    'currency' => (string) ($invoice->currency ?: 'EUR'),
                ]);
            }

            $bucket = $monthlyBuckets->get($month);

            if ($bucket === null) {
                continue;
            }

            $bucket['raw_total'] += $rawTotal;
            $bucket['raw_paid'] += $normalizedPaid;
            $bucket['raw_outstanding'] += $rawOutstanding;
            $bucket['invoice_count']++;

            $monthlyBuckets->put($month, $bucket);
        }

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $monthlyBuckets
            ->sortKeysDesc()
            ->values()
            ->map(fn (array $row): array => [
                'month' => (string) $row['month'],
                'raw_total' => (float) $row['raw_total'],
                'raw_paid' => (float) $row['raw_paid'],
                'raw_outstanding' => (float) $row['raw_outstanding'],
                'invoice_count' => (string) (int) $row['invoice_count'],
                'total_invoiced' => $this->formatCurrency((float) $row['raw_total'], (string) ($row['currency'] ?: 'EUR')),
                'total_paid' => $this->formatCurrency((float) $row['raw_paid'], (string) ($row['currency'] ?: 'EUR')),
                'total_outstanding' => $this->formatCurrency((float) $row['raw_outstanding'], (string) ($row['currency'] ?: 'EUR')),
            ])
            ->values();

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $rows;

        return [
            'title' => __('admin.reports.tabs.revenue'),
            'description' => __('admin.reports.descriptions.revenue_grouped'),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.months'),
                    'value' => (string) $rowData->count(),
                ],
                [
                    'label' => __('admin.reports.summary.total_invoiced'),
                    'value' => $this->formatCurrency((float) $rowData->sum('raw_total')),
                ],
                [
                    'label' => __('admin.reports.summary.total_paid'),
                    'value' => $this->formatCurrency((float) $rowData->sum('raw_paid')),
                ],
                [
                    'label' => __('admin.reports.summary.total_outstanding'),
                    'value' => $this->formatCurrency((float) $rowData->sum('raw_outstanding')),
                ],
            ],
            'columns' => [
                ['key' => 'month', 'label' => __('admin.reports.columns.month')],
                ['key' => 'total_invoiced', 'label' => __('admin.reports.columns.total_invoiced')],
                ['key' => 'total_paid', 'label' => __('admin.reports.columns.total_paid')],
                ['key' => 'total_outstanding', 'label' => __('admin.reports.columns.total_outstanding')],
                ['key' => 'invoice_count', 'label' => __('admin.reports.columns.invoice_count')],
            ],
            'rows' => $rowData->all(),
            'empty_state' => __('admin.reports.empty.revenue'),
        ];
    }
}
