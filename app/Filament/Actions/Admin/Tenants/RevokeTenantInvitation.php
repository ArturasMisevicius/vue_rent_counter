<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RevokeTenantInvitation
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, OrganizationInvitation $invitation): OrganizationInvitation
    {
        $tenant = $invitation->tenant;

        if (! $tenant instanceof User) {
            throw ValidationException::withMessages([
                'tenant' => __('auth.invitation_tenant_missing'),
            ]);
        }

        Gate::forUser($actor)->authorize('manageTenantPortalAccess', $tenant);

        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_used'),
            ]);
        }

        if (! $invitation->isRevoked()) {
            $invitation->forceFill([
                'revoked_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $invitation,
                [
                    'context' => [
                        'mutation' => 'tenant_invitation.revoked',
                    ],
                    'tenant' => [
                        'id' => $tenant->id,
                        'email' => $tenant->email,
                    ],
                ],
                actorUserId: $actor->id,
                description: "Tenant invitation revoked for {$tenant->email}",
            );
        }

        return $invitation->fresh([
            'organization:id,name',
            'tenant:id,organization_id,name,email,role,status,tenant_status,portal_access_enabled,locale',
        ]);
    }
}
