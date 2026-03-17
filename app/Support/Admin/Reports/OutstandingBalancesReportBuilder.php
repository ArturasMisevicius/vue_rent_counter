<?php

namespace App\Support\Admin\Reports;

use App\Models\Invoice;
use Illuminate\Support\Carbon;

class OutstandingBalancesReportBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $organizationId, Carbon $startDate, Carbon $endDate): array
    {
        return Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'total_amount',
                'amount_paid',
                'due_date',
                'billing_period_start',
                'billing_period_end',
            ])
            ->with([
                'property:id,name,unit_number',
                'tenant:id,name',
            ])
            ->where('organization_id', $organizationId)
            ->whereDate('billing_period_start', '>=', $startDate->toDateString())
            ->whereDate('billing_period_end', '<=', $endDate->toDateString())
            ->whereColumn('amount_paid', '<', 'total_amount')
            ->orderBy('due_date')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'tenant' => (string) ($invoice->tenant?->name ?? '—'),
                'property' => (string) ($invoice->property?->name ?? '—'),
                'balance' => round((float) $invoice->total_amount - (float) $invoice->amount_paid, 2),
                'due_date' => $invoice->due_date?->toDateString(),
            ])
            ->all();
    }
}
