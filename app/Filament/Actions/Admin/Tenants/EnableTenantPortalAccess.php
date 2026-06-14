<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EnableTenantPortalAccess
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, User $tenant): User
    {
        Gate::forUser($actor)->authorize('manageTenantPortalAccess', $tenant);

        if (! $tenant->portal_access_enabled) {
            $tenant->forceFill([
                'portal_access_enabled' => true,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $tenant,
                [
                    'context' => [
                        'mutation' => 'tenant_portal.enabled',
                    ],
                ],
                actorUserId: $actor->id,
                description: "Tenant portal enabled for {$tenant->email}",
            );
        }

        return $tenant->fresh([
            'currentPropertyAssignment.property',
            'latestTenantInvitation',
        ]);
    }
}
