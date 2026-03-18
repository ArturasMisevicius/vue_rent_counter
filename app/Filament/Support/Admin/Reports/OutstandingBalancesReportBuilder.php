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
        $invoices = Invoice::query()
            ->forAdminWorkspace($organizationId)
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
            ->whereBetween('billing_period_end', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', [
                InvoiceStatus::FINALIZED,
                InvoiceStatus::OVERDUE,
            ])
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
                ($filters['status_filter'] ?? 'all') === InvoiceStatus::FINALIZED->value,
                fn ($query) => $query->where('status', InvoiceStatus::FINALIZED),
            )
            ->reorder()
            ->orderBy('billing_period_end')
            ->orderBy('id')
            ->get();

        /** @var Collection<int, array<string, mixed>> $rowData */
        $rowData = $invoices
            ->map(function (Invoice $invoice): array {
                $outstanding = $invoice->outstanding_balance;
                $referenceDate = $invoice->billing_period_end ?? $invoice->due_date;
                $daysOverdue = ($referenceDate?->isPast() ?? false)
                    ? $referenceDate?->startOfDay()->diffInDays(now()->startOfDay())
                    : 0;
                $isOverdue = $outstanding > 0 && $daysOverdue > 0;

                return [
                    'invoice_id' => $invoice->id,
                    'raw_outstanding' => $outstanding,
                    'raw_days_overdue' => $daysOverdue,
                    'tenant_name' => (string) ($invoice->tenant?->name ?? ''),
                    'tenant_email' => (string) ($invoice->tenant?->email ?? ''),
                    'is_overdue' => $isOverdue,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'tenant' => (string) ($invoice->tenant?->name ?? __('dashboard.not_available')),
                    'property' => $this->propertyLabel($invoice->property),
                    'period_end' => $this->formatDate($invoice->billing_period_end),
                    'status' => __('admin.invoices.statuses.'.$invoice->status->value),
                    'days_overdue' => (string) $daysOverdue,
                    'outstanding' => $this->formatCurrency($outstanding, (string) ($invoice->currency ?? 'EUR')),
                    'reminder_sent_at' => $this->formatDate($invoice->last_reminder_sent_at),
                ];
            })
            ->filter(fn (array $row): bool => $row['raw_outstanding'] > 0)
            ->when(
                ($filters['only_overdue'] ?? false) || (($filters['status_filter'] ?? 'all') === 'overdue'),
                fn (Collection $rows): Collection => $rows->filter(fn (array $row): bool => (bool) $row['is_overdue']),
            )
            ->sortByDesc('raw_days_overdue')
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
