<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\OrganizationUsers;

use App\Enums\AuditLogAction;
use App\Enums\ManagerMembershipStatus;
use App\Enums\UserStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ToggleManagerStatusAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function disable(OrganizationUser $membership, User $actor): OrganizationUser
    {
        Gate::forUser($actor)->authorize('disableManager', $membership);

        return $this->transition(
            membership: $membership,
            actor: $actor,
            status: ManagerMembershipStatus::DISABLED,
            action: AuditLogAction::SUSPENDED,
            mutation: 'manager.disabled',
            description: 'Manager disabled',
        );
    }

    public function reactivate(OrganizationUser $membership, User $actor): OrganizationUser
    {
        Gate::forUser($actor)->authorize('reactivateManager', $membership);

        return $this->transition(
            membership: $membership,
            actor: $actor,
            status: ManagerMembershipStatus::ACTIVE,
            action: AuditLogAction::REINSTATED,
            mutation: 'manager.reactivated',
            description: 'Manager reactivated',
        );
    }

    private function transition(
        OrganizationUser $membership,
        User $actor,
        ManagerMembershipStatus $status,
        AuditLogAction $action,
        string $mutation,
        string $description,
    ): OrganizationUser {
        $membership->loadMissing(['user']);

        if ($membership->user === null) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_not_allowed'),
            ]);
        }

        return DB::transaction(function () use ($action, $actor, $description, $membership, $mutation, $status): OrganizationUser {
            $isActive = $status === ManagerMembershipStatus::ACTIVE;

            $membership->forceFill([
                'status' => $status,
                'is_active' => $isActive,
                'left_at' => $isActive ? null : now(),
                'accepted_at' => $isActive ? ($membership->accepted_at ?? now()) : $membership->accepted_at,
                'disabled_at' => $isActive ? null : now(),
            ])->save();

            $membership->user->forceFill([
                'status' => $isActive ? UserStatus::ACTIVE : UserStatus::SUSPENDED,
                'suspended_at' => $isActive ? null : now(),
                'suspension_reason' => $isActive ? null : __('admin.organization_users.messages.disabled_reason'),
            ])->save();

            $this->auditLogger->record(
                $action,
                $membership,
                [
                    'context' => [
                        'mutation' => $mutation,
                    ],
                    'manager' => [
                        'id' => $membership->user->id,
                        'name' => $membership->user->name,
                        'email' => $membership->user->email,
                    ],
                ],
                actorUserId: $actor->id,
                description: "{$description}: {$membership->user->email}",
            );

            return $membership->fresh(['organization', 'user', 'inviter', 'invitedBy']);
        });
    }
}
