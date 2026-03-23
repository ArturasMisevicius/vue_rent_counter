<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
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
        $baseQuery = Invoice::query()
            ->from('invoices')
            ->join('users as tenants', function (JoinClause $join): void {
                $join->on('tenants.id', '=', 'invoices.tenant_user_id');
                $join->on('tenants.organization_id', '=', 'invoices.organization_id');
            })
            ->leftJoin('properties', 'properties.id', '=', 'invoices.property_id')
            ->where('invoices.organization_id', $organizationId)
            ->whereBetween('invoices.billing_period_end', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->when(
                filled($filters['building_id'] ?? null),
                fn ($query) => $query->where('properties.building_id', (int) $filters['building_id']),
            )
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->where('invoices.property_id', (int) $filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->where('invoices.tenant_user_id', (int) $filters['tenant_id']),
            )
            ->when(
                filled($filters['status_filter'] ?? null) && $filters['status_filter'] !== 'all',
                fn ($query) => $query->where('invoices.status', (string) $filters['status_filter']),
            );

        $connectionDriver = $baseQuery->getConnection()->getDriverName();
        $monthExpression = $this->monthExpression($connectionDriver);
        $normalizedPaidExpression = 'CASE WHEN COALESCE(invoices.amount_paid, 0) >= COALESCE(invoices.paid_amount, 0) THEN COALESCE(invoices.amount_paid, 0) ELSE COALESCE(invoices.paid_amount, 0) END';
        $outstandingExpression = 'CASE WHEN invoices.total_amount - ('.$normalizedPaidExpression.') > 0 THEN invoices.total_amount - ('.$normalizedPaidExpression.') ELSE 0 END';

        $monthlyRows = $baseQuery
            ->select(new Expression($monthExpression.' AS report_month'))
            ->addSelect(new Expression('SUM(invoices.total_amount) AS total_invoiced_amount'))
            ->addSelect(new Expression('SUM('.$normalizedPaidExpression.') AS total_paid_amount'))
            ->addSelect(new Expression('SUM('.$outstandingExpression.') AS total_outstanding_amount'))
            ->addSelect(new Expression('COUNT(invoices.id) AS invoice_count'))
            ->addSelect(new Expression('MIN(invoices.currency) AS report_currency'))
            ->groupBy(new Expression($monthExpression))
            ->orderByDesc('report_month')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $monthlyRows
            ->map(fn (object $row): array => [
                'month' => (string) ($row->report_month ?? ''),
                'raw_total' => (float) ($row->total_invoiced_amount ?? 0),
                'raw_paid' => (float) ($row->total_paid_amount ?? 0),
                'raw_outstanding' => (float) ($row->total_outstanding_amount ?? 0),
                'invoice_count' => (string) (int) ($row->invoice_count ?? 0),
                'total_invoiced' => $this->formatCurrency((float) ($row->total_invoiced_amount ?? 0), (string) (($row->report_currency ?? 'EUR') ?: 'EUR')),
                'total_paid' => $this->formatCurrency((float) ($row->total_paid_amount ?? 0), (string) (($row->report_currency ?? 'EUR') ?: 'EUR')),
                'total_outstanding' => $this->formatCurrency((float) ($row->total_outstanding_amount ?? 0), (string) (($row->report_currency ?? 'EUR') ?: 'EUR')),
            ])
            ->filter(fn (array $row): bool => $row['month'] !== '')
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

    private function monthExpression(string $driver): string
    {
        return match ($driver) {
            'pgsql' => "to_char(date_trunc('month', invoices.billing_period_end), 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', invoices.billing_period_end)",
            default => "DATE_FORMAT(invoices.billing_period_end, '%Y-%m')",
        };
    }
}
