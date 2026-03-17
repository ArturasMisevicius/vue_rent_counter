<?php

namespace App\Support\Tenant\Portal;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;

class TenantInvoiceIndexQuery
{
    /**
     * @return Collection<int, Invoice>
     */
    public function for(User $tenant): Collection
    {
        return Invoice::query()
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
                'document_path',
            ])
            ->where('tenant_user_id', $tenant->id)
            ->with([
                'property:id,building_id,name,unit_number',
                'property.building:id,address_line_1,city',
            ])
            ->orderByDesc('billing_period_start')
            ->orderByDesc('id')
            ->get();
    }
}
