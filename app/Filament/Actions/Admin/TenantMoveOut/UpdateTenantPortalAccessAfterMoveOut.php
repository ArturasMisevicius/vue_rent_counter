<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\PortalAccessAfterMoveOut;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MoveOutProcess;
use App\Models\User;

final class UpdateTenantPortalAccessAfterMoveOut
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(MoveOutProcess $process, User $actor): User
    {
        $tenant = $process->tenant()
            ->select(['id', 'organization_id', 'role', 'status', 'tenant_status', 'portal_access_enabled'])
            ->firstOrFail();
        $before = $tenant->getOriginal();
        $policy = $process->portal_access_after_move_out;
        $portalAccessEnabled = match ($policy) {
            PortalAccessAfterMoveOut::DISABLE_IMMEDIATELY => false,
            PortalAccessAfterMoveOut::KEEP_HISTORICAL_ACCESS,
            PortalAccessAfterMoveOut::DISABLE_AFTER_FINAL_INVOICE_PAID,
            PortalAccessAfterMoveOut::DISABLE_AFTER_RETENTION_DAYS => (bool) $tenant->portal_access_enabled,
            default => (bool) $tenant->portal_access_enabled,
        };

        $tenant->forceFill([
            'portal_access_enabled' => $portalAccessEnabled,
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $tenant,
            [
                'context' => ['mutation' => 'tenant_move_out.portal_access_updated'],
                'move_out_process_id' => $process->id,
                'portal_access_after_move_out' => $policy?->value,
                'before' => $before,
                'after' => $tenant->getAttributes(),
            ],
            $actor->id,
            'Tenant portal access updated after move-out',
        );

        return $tenant->fresh() ?? $tenant;
    }
}
