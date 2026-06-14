<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\AuditLogAction;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SendTenantInvitation
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        User $actor,
        User $tenant,
        int $expirationDays = 7,
        bool $sendEmail = true,
    ): OrganizationInvitation {
        Gate::forUser($actor)->authorize('sendTenantInvitation', $tenant);

        $this->guardTenantCanBeInvited($tenant);

        $expirationDays = max(1, min($expirationDays, 60));
        $plainTextToken = OrganizationInvitation::issueToken();
        $tokenHash = OrganizationInvitation::hashToken($plainTextToken);

        return DB::transaction(function () use ($actor, $tenant, $expirationDays, $plainTextToken, $tokenHash, $sendEmail): OrganizationInvitation {
            OrganizationInvitation::query()
                ->forOrganization((int) $tenant->organization_id)
                ->where(function ($query) use ($tenant): void {
                    $query
                        ->where('tenant_id', $tenant->id)
                        ->orWhere(function ($query) use ($tenant): void {
                            $query
                                ->where('email', $tenant->email)
                                ->where('role', UserRole::TENANT);
                        });
                })
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                ]);

            $invitation = OrganizationInvitation::query()->create([
                'organization_id' => $tenant->organization_id,
                'tenant_id' => $tenant->id,
                'inviter_user_id' => $actor->id,
                'invited_by_user_id' => $actor->id,
                'email' => $tenant->email,
                'role' => UserRole::TENANT,
                'full_name' => $tenant->name,
                'token' => $tokenHash,
                'token_hash' => $tokenHash,
                'sent_at' => now(),
                'expires_at' => now()->addDays($expirationDays),
                'accepted_at' => null,
                'revoked_at' => null,
            ]);

            $invitation->acceptanceToken = $plainTextToken;

            $this->auditLogger->record(
                AuditLogAction::SENT,
                $invitation,
                [
                    'context' => [
                        'mutation' => 'tenant_invitation.sent',
                    ],
                    'tenant' => [
                        'id' => $tenant->id,
                        'email' => $tenant->email,
                    ],
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                ],
                actorUserId: $actor->id,
                description: "Tenant invitation sent to {$tenant->email}",
            );

            if ($sendEmail) {
                Notification::route('mail', $invitation->email)
                    ->notify(new OrganizationInvitationNotification($invitation->fresh(['organization', 'tenant']), $plainTextToken));
            }

            $freshInvitation = $invitation->fresh([
                'organization:id,name',
                'tenant:id,organization_id,name,email,role,status,tenant_status,portal_access_enabled,locale',
                'invitedBy:id,organization_id,name,email,role,status',
            ]);

            $freshInvitation->acceptanceToken = $plainTextToken;

            return $freshInvitation;
        });
    }

    private function guardTenantCanBeInvited(User $tenant): void
    {
        if (! $tenant->isTenant() || $tenant->organization_id === null) {
            throw ValidationException::withMessages([
                'tenant' => __('auth.invitation_not_allowed'),
            ]);
        }

        if (in_array($tenant->tenant_status, [
            TenantStatus::INACTIVE,
            TenantStatus::MOVED_OUT,
            TenantStatus::ARCHIVED,
        ], true)) {
            throw ValidationException::withMessages([
                'tenant' => __('auth.invitation_tenant_inactive'),
            ]);
        }
    }
}
