<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\OrganizationUsers;

use App\Enums\AuditLogAction;
use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Filament\Actions\Auth\CreateOrganizationInvitationAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ResendManagerInvitationAction
{
    public function __construct(
        private readonly CreateOrganizationInvitationAction $createOrganizationInvitationAction,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(OrganizationUser $membership, User $actor): OrganizationInvitation
    {
        Gate::forUser($actor)->authorize('resendInvitation', $membership);

        $membership->loadMissing(['organization', 'user']);

        if ($membership->organization === null || $membership->user === null) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_not_allowed'),
            ]);
        }

        return DB::transaction(function () use ($actor, $membership): OrganizationInvitation {
            $this->revokeOpenInvitations($membership);

            $invitation = $this->createOrganizationInvitationAction->handle($actor, [
                'email' => $membership->user->email,
                'role' => UserRole::MANAGER,
                'full_name' => $membership->user->name,
                'existing_user_id' => $membership->user->id,
                'send_notification' => true,
            ]);

            $membership->forceFill([
                'status' => ManagerMembershipStatus::INVITED,
                'is_active' => false,
                'left_at' => null,
                'disabled_at' => null,
                'invited_by' => $actor->id,
                'invited_by_user_id' => $actor->id,
                'invited_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::SENT,
                $membership,
                [
                    'context' => [
                        'mutation' => 'manager.invitation_resent',
                    ],
                    'manager' => [
                        'id' => $membership->user->id,
                        'name' => $membership->user->name,
                        'email' => $membership->user->email,
                    ],
                    'invitation' => [
                        'id' => $invitation->id,
                        'expires_at' => $invitation->expires_at?->toISOString(),
                    ],
                ],
                actorUserId: $actor->id,
                description: "Manager invitation resent: {$membership->user->email}",
            );

            return $invitation;
        });
    }

    private function revokeOpenInvitations(OrganizationUser $membership): void
    {
        OrganizationInvitation::query()
            ->where('organization_id', $membership->organization_id)
            ->where('email', $membership->user->email)
            ->where('role', UserRole::MANAGER)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }
}
