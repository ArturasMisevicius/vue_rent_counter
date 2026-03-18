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
                'invoice_number',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'billing_period_end',
                'paid_at',
            ])
            ->forOrganization($organizationId)
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($query) => $query->whereHas(
                    'property',
                    fn ($query) => $query->where('building_id', $filters['building_id']),
                ),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->forProperty($filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->forTenant($filters['tenant_id']),
            )
            ->when(
                filled($filters['status_filter'] ?? null) && $filters['status_filter'] !== 'all',
                fn ($query) => $query->where('status', $filters['status_filter']),
            )
            ->whereDate('billing_period_end', '>=', $startDate->toDateString())
            ->whereDate('billing_period_end', '<=', $endDate->toDateString())
            ->reorder()
            ->orderBy('billing_period_end')
            ->orderBy('id')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $invoices
            ->filter(fn (Invoice $invoice): bool => $invoice->billing_period_end !== null)
            ->groupBy(fn (Invoice $invoice): string => $invoice->billing_period_end->format('Y-m'))
            ->map(function (Collection $monthInvoices, string $month): array {
                $rawTotal = (float) $monthInvoices->sum(
                    fn (Invoice $invoice): float => (float) $invoice->total_amount
                );
                $rawPaid = (float) $monthInvoices->sum(
                    fn (Invoice $invoice): float => $invoice->normalized_paid_amount
                );
                $rawOutstanding = max($rawTotal - $rawPaid, 0.0);

                return [
                    'month' => $month,
                    'raw_total' => $rawTotal,
                    'raw_paid' => $rawPaid,
                    'raw_outstanding' => $rawOutstanding,
                    'invoice_count' => (string) $monthInvoices->count(),
                    'total_invoiced' => $this->formatCurrency($rawTotal),
                    'total_paid' => $this->formatCurrency($rawPaid),
                    'total_outstanding' => $this->formatCurrency($rawOutstanding),
                ];
            })
            ->values()
            ->sortByDesc('month')
            ->values();

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
