<?php

namespace App\Support\Admin\Reports;

use App\Models\Invoice;
use Illuminate\Support\Carbon;

class RevenueReportBuilder
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
                'status',
                'total_amount',
                'amount_paid',
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
            ->orderBy('billing_period_start')
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'tenant' => (string) ($invoice->tenant?->name ?? '—'),
                'property' => (string) ($invoice->property?->name ?? '—'),
                'total' => (float) $invoice->total_amount,
                'paid' => (float) $invoice->amount_paid,
                'status' => $invoice->status->value,
            ])
            ->all();
    }
}
