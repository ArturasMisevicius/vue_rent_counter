<?php

declare(strict_types=1);

namespace App\Filament\Actions\Auth;

use App\Enums\AuditLogAction;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\TenantInvitationAcceptedAdminNotification;
use App\Notifications\TenantPortalActivatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptTenantInvitation
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{name: string, password: string}  $attributes
     */
    public function handle(OrganizationInvitation $invitation, array $attributes, string $locale): User
    {
        return DB::transaction(function () use ($invitation, $attributes, $locale): User {
            $lockedInvitation = OrganizationInvitation::query()
                ->with([
                    'organization:id,name',
                    'tenant:id,organization_id,name,email,role,status,tenant_status,portal_access_enabled,locale,password',
                    'invitedBy:id,organization_id,name,email,role,status',
                    'inviter:id,organization_id,name,email,role,status',
                ])
                ->whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardInvitationCanBeAccepted($lockedInvitation);

            $tenant = $lockedInvitation->tenant;

            if (! $tenant instanceof User || ! $tenant->isTenant()) {
                throw ValidationException::withMessages([
                    'email' => __('auth.invitation_tenant_missing'),
                ]);
            }

            if (! $tenant->canAcceptTenantInvitation()) {
                throw ValidationException::withMessages([
                    'email' => __('auth.invitation_tenant_inactive'),
                ]);
            }

            $tenant->forceFill([
                'name' => $attributes['name'],
                'email' => $lockedInvitation->email,
                'password' => $attributes['password'],
                'status' => UserStatus::ACTIVE,
                'tenant_status' => TenantStatus::ACTIVE,
                'portal_access_enabled' => true,
                'locale' => $locale,
                'email_verified_at' => now(),
            ])->save();

            $lockedInvitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $lockedInvitation,
                [
                    'context' => [
                        'mutation' => 'tenant_invitation.accepted',
                    ],
                    'tenant' => [
                        'id' => $tenant->id,
                        'email' => $tenant->email,
                    ],
                ],
                actorUserId: $tenant->id,
                description: "Tenant invitation accepted by {$tenant->email}",
            );

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $tenant,
                [
                    'context' => [
                        'mutation' => 'tenant_portal.activated',
                    ],
                ],
                actorUserId: $tenant->id,
                description: "Tenant portal activated for {$tenant->email}",
            );

            $tenant->notify(new TenantPortalActivatedNotification($lockedInvitation->organization));

            $admin = $lockedInvitation->invitedBy ?? $lockedInvitation->inviter;

            if ($admin instanceof User && ! $admin->is($tenant)) {
                $admin->notify(new TenantInvitationAcceptedAdminNotification($tenant, $lockedInvitation));
            }

            return $tenant->fresh([
                'organization:id,name',
                'latestTenantInvitation',
            ]);
        });
    }

    private function guardInvitationCanBeAccepted(OrganizationInvitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_used'),
            ]);
        }

        if ($invitation->isRevoked()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_revoked'),
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_expired'),
            ]);
        }
    }
}
