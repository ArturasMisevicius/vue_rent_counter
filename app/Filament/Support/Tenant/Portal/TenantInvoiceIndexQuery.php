<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;

class TenantInvoiceIndexQuery
{
    public function for(User $tenant, ?string $status = null): Paginator
    {
        $query = Invoice::query()->forTenantWorkspace($tenant->organization_id, $tenant->id);

        match ($status) {
            'unpaid', 'outstanding' => $query->outstanding(),
            'paid' => $query->where('status', InvoiceStatus::PAID),
            default => null,
        };

        return $query->simplePaginate(10)->withQueryString();
    }
}
