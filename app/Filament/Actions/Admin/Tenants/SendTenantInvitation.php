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
use Illuminate\Database\Eloquent\Builder;
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
        ?string $auditMutation = null,
        ?string $auditDelivery = null,
    ): OrganizationInvitation {
        if (Gate::forUser($actor)->denies('sendTenantInvitation', $tenant)) {
            $this->recordForbiddenInvitationAttempt($actor, $tenant);

            Gate::forUser($actor)->authorize('sendTenantInvitation', $tenant);
        }

        $this->guardTenantCanBeInvited($tenant);

        $expirationDays = max(1, min($expirationDays, 60));
        $plainTextToken = OrganizationInvitation::issueToken();
        $tokenHash = OrganizationInvitation::hashToken($plainTextToken);
        $mutation = $auditMutation ?? ($sendEmail ? 'tenant_invitation.sent' : 'tenant_invitation.link_created');
        $delivery = $auditDelivery ?? ($sendEmail ? 'email' : 'manual_link');

        return DB::transaction(function () use ($actor, $tenant, $expirationDays, $plainTextToken, $tokenHash, $sendEmail, $mutation, $delivery): OrganizationInvitation {
            $pendingInvitationsQuery = OrganizationInvitation::query()
                ->forOrganization((int) $tenant->organization_id)
                ->where(function (Builder $query) use ($tenant): void {
                    $query
                        ->where('tenant_id', $tenant->id)
                        ->orWhere(function (Builder $query) use ($tenant): void {
                            $query
                                ->where('email', $tenant->email)
                                ->where('role', UserRole::TENANT);
                        });
                })
                ->whereNull('accepted_at')
                ->whereNull('revoked_at');

            $revokedInvitationIds = (clone $pendingInvitationsQuery)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $pendingInvitationsQuery->update([
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
                        'mutation' => $mutation,
                        'delivery' => $delivery,
                    ],
                    'tenant' => [
                        'id' => $tenant->id,
                        'email' => $tenant->email,
                    ],
                    'revoked_invitation_ids' => $revokedInvitationIds,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                ],
                actorUserId: $actor->id,
                description: $this->descriptionForMutation($mutation, $tenant),
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

    private function recordForbiddenInvitationAttempt(User $actor, User $tenant): void
    {
        if ($tenant->organization_id === null) {
            return;
        }

        $this->auditLogger->record(
            AuditLogAction::REJECTED,
            $tenant,
            [
                'context' => [
                    'mutation' => 'tenant_invitation.forbidden_access_attempt',
                    'reason' => $this->forbiddenReason($actor, $tenant),
                    'actor_role' => $this->roleValue($actor->role),
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'email' => $tenant->email,
                ],
            ],
            actorUserId: $actor->id,
            description: "Forbidden tenant invitation attempt: {$actor->email}",
        );
    }

    private function forbiddenReason(User $actor, User $tenant): string
    {
        if (! $tenant->isTenant()) {
            return 'invalid_tenant_profile';
        }

        if (! $actor->isSuperadmin() && $actor->organization_id !== $tenant->organization_id) {
            return 'organization_scope_denied';
        }

        if ($actor->isTenant()) {
            return 'tenant_role_denied';
        }

        if ($actor->isManager()) {
            return 'manager_permission_denied';
        }

        return 'actor_role_denied';
    }

    private function descriptionForMutation(string $mutation, User $tenant): string
    {
        return match ($mutation) {
            'tenant_invitation.resent' => "Tenant invitation resent to {$tenant->email}",
            'tenant_invitation.link_created' => "Tenant invitation link created for {$tenant->email}",
            default => "Tenant invitation sent to {$tenant->email}",
        };
    }

    private function roleValue(mixed $role): string
    {
        return $role instanceof UserRole ? $role->value : (string) $role;
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
