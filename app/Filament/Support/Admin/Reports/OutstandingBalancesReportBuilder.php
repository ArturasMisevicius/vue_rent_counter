<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Reports;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\CarbonInterface;
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

        $rows = Invoice::query()
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
            ->with([
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
            ])
            ->whereDate('billing_period_end', '>=', $startDate->toDateString())
            ->whereDate('billing_period_end', '<=', $endDate->toDateString())
            ->whereIn('status', [
                InvoiceStatus::FINALIZED->value,
                InvoiceStatus::OVERDUE->value,
            ])
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
                $statusFilter === InvoiceStatus::FINALIZED->value,
                fn ($query) => $query->where('status', InvoiceStatus::FINALIZED->value),
            )
            ->get();

        /** @var Collection<int, array<string, mixed>> $rows */
        $rowData = $rows
            ->map(function (Invoice $invoice): array {
                $normalizedPaid = $this->normalizedPaidAmount($invoice->amount_paid, $invoice->paid_amount);
                $outstanding = $this->outstandingAmount($invoice->total_amount, $normalizedPaid);
                $daysOverdue = $this->daysOverdue($invoice->billing_period_end ?? $invoice->due_date);
                $isOverdue = $daysOverdue > 0 && $outstanding > 0;
                $property = $invoice->property;
                $buildingName = $property?->building?->name;
                $tenant = $invoice->tenant;

                return [
                    'invoice_id' => (int) $invoice->id,
                    'raw_outstanding' => $outstanding,
                    'raw_days_overdue' => $daysOverdue,
                    'tenant_name' => (string) ($tenant?->name ?? ''),
                    'tenant_email' => (string) ($tenant?->email ?? ''),
                    'is_overdue' => $isOverdue,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'tenant' => (string) ($tenant?->name ?: __('dashboard.not_available')),
                    'property' => trim(implode(' · ', array_filter([
                        (string) ($property?->name ?? ''),
                        (string) ($property?->unit_number ?? ''),
                        (string) ($buildingName ?? ''),
                    ]))) ?: __('dashboard.not_available'),
                    'period_end' => $this->formatDate($invoice->billing_period_end),
                    'status' => __('admin.invoices.statuses.'.(string) $invoice->status->value),
                    'days_overdue' => (string) $daysOverdue,
                    'outstanding' => $this->formatCurrency($outstanding, (string) ($invoice->currency ?: 'EUR')),
                    'reminder_sent_at' => $this->formatDate($invoice->last_reminder_sent_at),
                ];
            })
            ->filter(fn (array $row): bool => (float) $row['raw_outstanding'] > 0)
            ->when(
                ($filters['only_overdue'] ?? false) || $statusFilter === 'overdue',
                fn (Collection $rows): Collection => $rows->where('raw_days_overdue', '>', 0),
            )
            ->sortBy([
                ['raw_days_overdue', 'desc'],
                ['tenant_email', 'asc'],
            ])
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
