<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\OrganizationUsers;

use App\Enums\AuditLogAction;
use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RevokeManagerInvitationAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(OrganizationUser $membership, User $actor): OrganizationUser
    {
        Gate::forUser($actor)->authorize('revokeInvitation', $membership);

        $membership->loadMissing(['user']);

        if ($membership->user === null) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_not_allowed'),
            ]);
        }

        return DB::transaction(function () use ($actor, $membership): OrganizationUser {
            OrganizationInvitation::query()
                ->where('organization_id', $membership->organization_id)
                ->where('email', $membership->user->email)
                ->where('role', UserRole::MANAGER)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                ]);

            $membership->forceFill([
                'status' => ManagerMembershipStatus::EXPIRED,
                'is_active' => false,
                'left_at' => now(),
                'disabled_at' => null,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $membership,
                [
                    'context' => [
                        'mutation' => 'manager.invitation_revoked',
                    ],
                    'manager' => [
                        'id' => $membership->user->id,
                        'name' => $membership->user->name,
                        'email' => $membership->user->email,
                    ],
                ],
                actorUserId: $actor->id,
                description: "Manager invitation revoked: {$membership->user->email}",
            );

            return $membership->fresh(['organization', 'user', 'inviter', 'invitedBy']);
        });
    }
}
