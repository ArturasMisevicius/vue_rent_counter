<?php

namespace App\Support\Tenant\Portal;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;

class TenantInvoiceIndexQuery
{
    public function for(User $tenant, ?string $status = null): Paginator
    {
        $query = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'due_date',
                'paid_at',
                'document_path',
            ])
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
            ])
            ->where('tenant_user_id', $tenant->id)
            ->orderByDesc('billing_period_start')
            ->orderByDesc('id');

        match ($status) {
            'unpaid', 'outstanding' => $query->whereIn('status', [
                InvoiceStatus::FINALIZED->value,
                InvoiceStatus::OVERDUE->value,
                InvoiceStatus::PARTIALLY_PAID->value,
            ]),
            'paid' => $query->where('status', InvoiceStatus::PAID->value),
            default => null,
        };

        return $query->simplePaginate(10)->withQueryString();
    }
}
