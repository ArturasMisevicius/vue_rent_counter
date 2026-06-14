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
            'payments:id,invoice_id,organization_id,tenant_id,property_id,amount,currency,method,payment_method,status,payment_date,reference,transaction_id,paid_at,confirmed_at,rejected_at,rejection_reason,voided_at,void_reason,tenant_comment,created_at',
            'payments.attachments:id,organization_id,attachable_type,attachable_id,uploaded_by_user_id,filename,original_filename,mime_type,size,disk,path,document_type,tenant_visible,created_at',
            'invoiceItems:id,invoice_id,source_type,source_id,title,description,description_for_tenant,quantity,unit,unit_price,subtotal,tax_amount,discount_amount,total,currency,formula_label,calculation_snapshot,tenant_visible,sort_order,meter_reading_snapshot,service_snapshot,tariff_snapshot,provider_snapshot',
        ]);

        match ($status) {
            'unpaid', 'outstanding' => $query->outstanding(),
            'paid' => $query->where('status', InvoiceStatus::PAID),
            default => null,
        };

        return $query->paginate(10)->withQueryString();
    }
}
