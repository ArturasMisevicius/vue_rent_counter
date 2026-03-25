<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenantInvoiceIndexQuery
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    public function for(User $tenant, ?string $status = null): LengthAwarePaginator
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null) {
            return Invoice::query()
                ->whereKey(-1)
                ->paginate(10)
                ->withQueryString();
        }

        $query = Invoice::query()->forTenantWorkspace(
            $workspace->organizationId,
            $workspace->userId,
            $workspace->propertyId,
        );

        $query->with([
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at,notes',
            'invoiceItems:id,invoice_id,description,quantity,unit,unit_price,total,meter_reading_snapshot',
        ]);

        match ($status) {
            'unpaid', 'outstanding' => $query->outstanding(),
            'paid' => $query->where('status', InvoiceStatus::PAID),
            default => null,
        };

        return $query->paginate(10)->withQueryString();
    }
}
