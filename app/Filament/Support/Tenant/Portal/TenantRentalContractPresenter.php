<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\RentalContract;
use App\Models\User;

class TenantRentalContractPresenter
{
    public function __construct(
        private readonly WorkspaceResolver $workspaceResolver,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function for(User $tenant): array
    {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null) {
            return [];
        }

        return RentalContract::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'property_id',
                'contract_number',
                'status',
                'start_date',
                'end_date',
                'signed_date',
                'rent_amount',
                'deposit_amount',
                'currency',
                'tenant_visible',
                'tenant_visible_notes',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($workspace->organizationId)
            ->forTenant($workspace->userId)
            ->visibleToTenant()
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name,address_line_1,city',
                'file:attachments.id,attachments.organization_id,attachments.attachable_type,attachments.attachable_id,attachments.uploaded_by_user_id,attachments.filename,attachments.original_filename,attachments.mime_type,attachments.size,attachments.disk,attachments.path,attachments.document_type,attachments.created_at',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RentalContract $contract): array => $this->present($contract))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(RentalContract $contract): array
    {
        return [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'status' => $contract->status?->label() ?? (string) $contract->status?->value,
            'property' => $contract->property?->tenantAssignmentLabel() ?? __('dashboard.not_available'),
            'period' => collect([
                $contract->start_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                $contract->end_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
            ])->filter()->implode(' - '),
            'signed_date' => $contract->signed_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? __('dashboard.not_available'),
            'rent_amount' => $contract->rent_amount === null
                ? __('dashboard.not_available')
                : EuMoneyFormatter::format((float) $contract->rent_amount, $contract->currency),
            'deposit_amount' => $contract->deposit_amount === null
                ? __('dashboard.not_available')
                : EuMoneyFormatter::format((float) $contract->deposit_amount, $contract->currency),
            'tenant_visible_notes' => $contract->tenant_visible_notes,
            'file_name' => $contract->file?->original_filename ?: $contract->file?->filename,
            'download_url' => $contract->file === null
                ? null
                : route('tenant.rental-contracts.download', [$contract, $contract->file]),
        ];
    }
}
