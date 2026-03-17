<?php

namespace App\Support\Admin\Reports;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class OutstandingBalancesReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{only_overdue: bool}  $filters
     * @return array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, string>>,
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
                'due_date',
            ])
            ->with([
                'property:id,building_id,name,unit_number',
                'property.building:id,name',
                'tenant:id,name',
            ])
            ->where('organization_id', $organizationId)
            ->whereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('status', [
                InvoiceStatus::PAID,
                InvoiceStatus::VOID,
            ])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        /** @var Collection<int, array<string, bool|float|string>> $rowData */
        $rowData = $invoices
            ->map(function (Invoice $invoice): array {
                $paidAmount = max((float) $invoice->amount_paid, (float) $invoice->paid_amount);
                $outstanding = max((float) $invoice->total_amount - $paidAmount, 0);
                $isOverdue = $outstanding > 0 && $invoice->due_date?->isPast();

                return [
                    'raw_outstanding' => $outstanding,
                    'tenant_name' => (string) ($invoice->tenant?->name ?? ''),
                    'is_overdue' => $isOverdue,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'tenant' => (string) ($invoice->tenant?->name ?? __('dashboard.not_available')),
                    'property' => $this->propertyLabel($invoice->property),
                    'due_date' => $this->formatDate($invoice->due_date),
                    'status' => __('admin.invoices.statuses.'.$invoice->status->value),
                    'outstanding' => $this->formatCurrency($outstanding, (string) ($invoice->currency ?? 'EUR')),
                ];
            })
            ->filter(fn (array $row): bool => $row['raw_outstanding'] > 0)
            ->when(
                $filters['only_overdue'] ?? false,
                fn (Collection $rows): Collection => $rows->filter(fn (array $row): bool => (bool) $row['is_overdue']),
            )
            ->values();

        return [
            'title' => __('admin.reports.tabs.outstanding_balances'),
            'description' => __('admin.reports.descriptions.outstanding_balances'),
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
                ['key' => 'due_date', 'label' => __('admin.reports.columns.due_date')],
                ['key' => 'status', 'label' => __('admin.reports.columns.status')],
                ['key' => 'outstanding', 'label' => __('admin.reports.columns.outstanding')],
            ],
            'rows' => $rowData
                ->map(fn (array $row): array => Arr::except($row, ['raw_outstanding', 'tenant_name', 'is_overdue']))
                ->all(),
            'empty_state' => __('admin.reports.empty.outstanding_balances'),
        ];
    }
}
