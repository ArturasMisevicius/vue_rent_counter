<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

final class OutstandingBalancesReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{building_id: int|null, property_id: int|null, tenant_id: int|null, only_overdue: bool, status_filter: string|null}  $filters
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
        $statusFilter = (string) ($filters['status_filter'] ?? 'all');
        $daysOverdueExpression = $this->daysOverdueExpression(
            Invoice::query()->getConnection()->getDriverName(),
            'invoices.billing_period_end',
        );
        $normalizedPaidExpression = 'CASE WHEN COALESCE(invoices.amount_paid, 0) >= COALESCE(invoices.paid_amount, 0) THEN COALESCE(invoices.amount_paid, 0) ELSE COALESCE(invoices.paid_amount, 0) END';
        $outstandingExpression = 'CASE WHEN invoices.total_amount - ('.$normalizedPaidExpression.') > 0 THEN invoices.total_amount - ('.$normalizedPaidExpression.') ELSE 0 END';

        $rows = Invoice::query()
            ->from('invoices')
            ->select([
                'invoices.id as invoice_id',
                'invoices.invoice_number',
                'invoices.status',
                'invoices.currency',
                'invoices.total_amount',
                'invoices.amount_paid',
                'invoices.paid_amount',
                'invoices.billing_period_end',
                'invoices.last_reminder_sent_at',
                'tenants.name as tenant_name',
                'tenants.email as tenant_email',
                'properties.name as property_name',
                'properties.unit_number as property_unit',
                'buildings.name as building_name',
            ])
            ->addSelect(new Expression($daysOverdueExpression.' AS days_overdue'))
            ->addSelect(new Expression($outstandingExpression.' AS outstanding_amount'))
            ->join('users as tenants', function (JoinClause $join): void {
                $join->on('tenants.id', '=', 'invoices.tenant_user_id');
                $join->on('tenants.organization_id', '=', 'invoices.organization_id');
            })
            ->leftJoin('properties', 'properties.id', '=', 'invoices.property_id')
            ->leftJoin('buildings', 'buildings.id', '=', 'properties.building_id')
            ->where('invoices.organization_id', $organizationId)
            ->whereBetween('invoices.billing_period_end', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->whereIn('invoices.status', [
                InvoiceStatus::FINALIZED->value,
                InvoiceStatus::OVERDUE->value,
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
                $statusFilter === InvoiceStatus::FINALIZED->value,
                fn ($query) => $query->where('invoices.status', InvoiceStatus::FINALIZED->value),
            )
            ->when(
                ($filters['only_overdue'] ?? false) || $statusFilter === 'overdue',
                fn ($query) => $query->where(new Expression($daysOverdueExpression), '>', 0),
            )
            ->where(new Expression($outstandingExpression), '>', 0)
            ->orderByDesc('days_overdue')
            ->orderBy('tenant_email')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rows */
        $rowData = $rows
            ->map(function (object $row): array {
                $daysOverdue = (int) ($row->days_overdue ?? 0);
                $outstanding = (float) ($row->outstanding_amount ?? 0);
                $isOverdue = $daysOverdue > 0 && $outstanding > 0;

                $propertyLabel = trim(implode(' · ', array_filter([
                    (string) ($row->property_name ?? ''),
                    (string) ($row->property_unit ?? ''),
                    (string) ($row->building_name ?? ''),
                ])));

                $statusLabel = $row->status instanceof InvoiceStatus
                    ? $row->status->value
                    : (string) ($row->status ?? '');
                $statusTranslationKey = 'admin.invoices.statuses.'.$statusLabel;

                return [
                    'invoice_id' => (int) ($row->invoice_id ?? 0),
                    'raw_outstanding' => $outstanding,
                    'raw_days_overdue' => $daysOverdue,
                    'tenant_name' => (string) ($row->tenant_name ?? ''),
                    'tenant_email' => (string) ($row->tenant_email ?? ''),
                    'is_overdue' => $isOverdue,
                    'invoice_number' => (string) ($row->invoice_number ?? ''),
                    'tenant' => (string) (($row->tenant_name ?? '') !== '' ? $row->tenant_name : __('dashboard.not_available')),
                    'property' => $propertyLabel !== '' ? $propertyLabel : __('dashboard.not_available'),
                    'period_end' => $this->formatDate($row->billing_period_end ?? null),
                    'status' => __($statusTranslationKey),
                    'days_overdue' => (string) $daysOverdue,
                    'outstanding' => $this->formatCurrency($outstanding, (string) (($row->currency ?? 'EUR') ?: 'EUR')),
                    'reminder_sent_at' => $this->formatDate($row->last_reminder_sent_at ?? null),
                ];
            })
            ->values();

        return [
            'title' => __('admin.reports.tabs.outstanding_balances'),
            'description' => __('admin.reports.descriptions.outstanding_balances_grouped'),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.outstanding_total'),
                    'value' => $this->formatCurrency((float) $rowData->sum('raw_outstanding')),
                ],
                [
                    'label' => __('admin.reports.summary.overdue_count'),
                    'value' => (string) $rowData->where('is_overdue', true)->count(),
                ],
                [
                    'label' => __('admin.reports.summary.affected_tenants'),
                    'value' => (string) $rowData->pluck('tenant_name')->filter()->unique()->count(),
                ],
            ],
            'columns' => [
                ['key' => 'invoice_number', 'label' => __('admin.reports.columns.invoice')],
                ['key' => 'tenant', 'label' => __('admin.reports.columns.tenant')],
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'period_end', 'label' => __('admin.reports.columns.period_end')],
                ['key' => 'status', 'label' => __('admin.reports.columns.status')],
                ['key' => 'days_overdue', 'label' => __('admin.reports.columns.days_overdue')],
                ['key' => 'outstanding', 'label' => __('admin.reports.columns.outstanding')],
                ['key' => 'reminder_sent_at', 'label' => __('admin.reports.columns.reminder_sent_at')],
            ],
            'rows' => $rowData->all(),
            'empty_state' => __('admin.reports.empty.outstanding_balances'),
        ];
    }

    private function daysOverdueExpression(string $driver, string $column): string
    {
        return match ($driver) {
            'pgsql' => "CASE WHEN (CURRENT_DATE - CAST({$column} as date)) > 0 THEN (CURRENT_DATE - CAST({$column} as date)) ELSE 0 END",
            'sqlite' => "CASE WHEN CAST(julianday(date('now')) - julianday(date({$column})) AS INTEGER) > 0 THEN CAST(julianday(date('now')) - julianday(date({$column})) AS INTEGER) ELSE 0 END",
            default => "CASE WHEN DATEDIFF(CURDATE(), DATE({$column})) > 0 THEN DATEDIFF(CURDATE(), DATE({$column})) ELSE 0 END",
        };
    }
}
