<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\CarbonInterface;

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

        $rowData = Invoice::query()
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
                'due_date',
                'last_reminder_sent_at',
            ])
            ->forOrganization($organizationId)
            ->whereIn('status', InvoiceStatus::outstandingValues())
            ->where(function ($query) use ($startDate, $endDate): void {
                $query
                    ->where(function ($dueDateQuery) use ($startDate, $endDate): void {
                        $dueDateQuery
                            ->whereNotNull('due_date')
                            ->whereBetween('due_date', [
                                $startDate->toDateString(),
                                $endDate->toDateString(),
                            ]);
                    })
                    ->orWhere(function ($periodEndQuery) use ($startDate, $endDate): void {
                        $periodEndQuery
                            ->whereNull('due_date')
                            ->whereBetween('billing_period_end', [
                                $startDate->toDateString(),
                                $endDate->toDateString(),
                            ]);
                    });
            })
            ->when(
                filled($filters['property_id'] ?? null),
                fn ($query) => $query->where('property_id', (int) $filters['property_id']),
            )
            ->when(
                filled($filters['tenant_id'] ?? null),
                fn ($query) => $query->where('tenant_user_id', (int) $filters['tenant_id']),
            )
            ->with([
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
            ])
            ->get()
            ->filter(function (Invoice $invoice) use ($filters): bool {
                if (! filled($filters['building_id'] ?? null)) {
                    return true;
                }

                return $invoice->property?->building_id === (int) $filters['building_id'];
            })
            ->map(function (Invoice $invoice): array {
                $daysOverdue = $invoice->overdueDays();
                $propertyLabel = trim(implode(' · ', array_filter([
                    (string) ($invoice->property?->displayName() ?? ''),
                    (string) ($invoice->property?->unit_number ?? ''),
                    (string) ($invoice->property?->building?->displayName() ?? ''),
                ])));

                return [
                    'invoice_id' => $invoice->id,
                    'raw_outstanding' => $invoice->outstanding_balance,
                    'raw_days_overdue' => $daysOverdue,
                    'tenant_name' => (string) ($invoice->tenant?->name ?? ''),
                    'tenant_email' => (string) ($invoice->tenant?->email ?? ''),
                    'status_value' => $invoice->effectiveStatus()->value,
                    'is_overdue' => $invoice->isOverdue(),
                    'invoice_number' => (string) $invoice->invoice_number,
                    'tenant' => (string) (($invoice->tenant?->name ?? '') !== '' ? $invoice->tenant?->name : __('dashboard.not_available')),
                    'property' => $propertyLabel !== '' ? $propertyLabel : __('dashboard.not_available'),
                    'period_end' => $this->formatDate($invoice->billing_period_end),
                    'status' => $invoice->effectiveStatus()->label(),
                    'days_overdue' => (string) $daysOverdue,
                    'outstanding' => $this->formatCurrency($invoice->outstanding_balance, (string) ($invoice->currency ?: 'EUR')),
                    'reminder_sent_at' => $this->formatDate($invoice->last_reminder_sent_at),
                ];
            })
            ->filter(function (array $row) use ($filters, $statusFilter): bool {
                if (($filters['only_overdue'] ?? false) || $statusFilter === 'overdue') {
                    return $row['is_overdue'];
                }

                if ($statusFilter === InvoiceStatus::FINALIZED->value) {
                    return $row['status_value'] === InvoiceStatus::FINALIZED->value;
                }

                return true;
            })
            ->filter(fn (array $row): bool => $row['raw_outstanding'] > 0)
            ->sort(function (array $left, array $right): int {
                $daysComparison = $right['raw_days_overdue'] <=> $left['raw_days_overdue'];

                if ($daysComparison !== 0) {
                    return $daysComparison;
                }

                return strcmp($left['tenant_email'], $right['tenant_email']);
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
}
