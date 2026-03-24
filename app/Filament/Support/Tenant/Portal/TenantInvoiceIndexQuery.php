<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;

class TenantInvoiceIndexQuery
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    public function for(User $tenant, ?string $status = null): Paginator
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null) {
            return Invoice::query()
                ->whereKey(-1)
                ->simplePaginate(10)
                ->withQueryString();
        }

        $query = Invoice::query()->forTenantWorkspace(
            $workspace->organizationId,
            $workspace->userId,
            $workspace->propertyId,
        );

        $query->with([
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at,notes',
        ]);

        match ($status) {
            'unpaid', 'outstanding' => $query->outstanding(),
            'paid' => $query->where('status', InvoiceStatus::PAID),
            default => null,
        };

        return $query->simplePaginate(10)->withQueryString();
    }
}
