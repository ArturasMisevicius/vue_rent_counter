<?php

namespace App\Support\Admin\Reports;

use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RevenueReportBuilder extends AbstractReportBuilder
{
    /**
     * @param  array{invoice_status: string|null}  $filters
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
                'billing_period_end',
                'paid_at',
            ])
            ->with([
                'property:id,building_id,name,unit_number',
                'property.building:id,name',
                'tenant:id,name',
            ])
            ->where('organization_id', $organizationId)
            ->whereBetween('billing_period_end', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(
                filled($filters['invoice_status'] ?? null),
                fn ($query) => $query->where('status', $filters['invoice_status']),
            )
            ->orderByDesc('billing_period_end')
            ->orderByDesc('id')
            ->get();

        /** @var Collection<int, array<string, float|string>> $rowData */
        $rowData = $invoices
            ->map(function (Invoice $invoice): array {
                $paidAmount = max((float) $invoice->amount_paid, (float) $invoice->paid_amount);
                $outstanding = max((float) $invoice->total_amount - $paidAmount, 0);
                $currency = (string) ($invoice->currency ?? 'EUR');

                return [
                    'raw_total' => (float) $invoice->total_amount,
                    'raw_paid' => $paidAmount,
                    'raw_outstanding' => $outstanding,
                    'invoice_number' => (string) $invoice->invoice_number,
                    'tenant' => (string) ($invoice->tenant?->name ?? __('dashboard.not_available')),
                    'property' => $this->propertyLabel($invoice->property),
                    'status' => __('admin.invoices.statuses.'.$invoice->status->value),
                    'billed' => $this->formatCurrency((float) $invoice->total_amount, $currency),
                    'paid' => $this->formatCurrency($paidAmount, $currency),
                    'outstanding' => $this->formatCurrency($outstanding, $currency),
                    'period_end' => $this->formatDate($invoice->billing_period_end),
                ];
            })
            ->values();

        return [
            'title' => __('admin.reports.tabs.revenue'),
            'description' => __('admin.reports.descriptions.revenue'),
            'summary' => [
                [
                    'label' => __('admin.reports.summary.invoice_count'),
                    'value' => (string) $rowData->count(),
                ],
                [
                    'label' => __('admin.reports.summary.billed_total'),
                    'value' => $this->formatCurrency((float) $rowData->sum('raw_total')),
                ],
                [
                    'label' => __('admin.reports.summary.collected_total'),
                    'value' => $this->formatCurrency((float) $rowData->sum('raw_paid')),
                ],
            ],
            'columns' => [
                ['key' => 'invoice_number', 'label' => __('admin.reports.columns.invoice')],
                ['key' => 'tenant', 'label' => __('admin.reports.columns.tenant')],
                ['key' => 'property', 'label' => __('admin.reports.columns.property')],
                ['key' => 'status', 'label' => __('admin.reports.columns.status')],
                ['key' => 'billed', 'label' => __('admin.reports.columns.billed')],
                ['key' => 'paid', 'label' => __('admin.reports.columns.paid')],
                ['key' => 'outstanding', 'label' => __('admin.reports.columns.outstanding')],
                ['key' => 'period_end', 'label' => __('admin.reports.columns.period_end')],
            ],
            'rows' => $rowData
                ->map(fn (array $row): array => Arr::except($row, ['raw_total', 'raw_paid', 'raw_outstanding']))
                ->all(),
            'empty_state' => __('admin.reports.empty.revenue'),
        ];
    }
}
