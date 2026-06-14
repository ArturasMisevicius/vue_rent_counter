<?php

declare(strict_types=1);

namespace App\Filament\Actions\Auth;

use App\Enums\AuditLogAction;
use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Notifications\Admin\ManagerInvitationAcceptedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptOrganizationInvitationAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{name: string, password: string}  $attributes
     */
    public function handle(OrganizationInvitation $invitation, array $attributes, string $locale): User
    {
        if ($invitation->isRevoked()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_revoked'),
            ]);
        }

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_expired'),
            ]);
        }

        $existingUser = User::query()
            ->where('email', $invitation->email)
            ->first();

        if (
            $existingUser !== null
            && (
                $existingUser->organization_id !== $invitation->organization_id
                || $existingUser->role !== $invitation->role
            )
        ) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_email_exists'),
            ]);
        }

        return DB::transaction(function () use ($invitation, $attributes, $existingUser, $locale): User {
            if ($existingUser !== null) {
                $existingUser->forceFill([
                    'name' => $attributes['name'],
                    'password' => $attributes['password'],
                    'status' => UserStatus::ACTIVE,
                    'locale' => $locale,
                ])->save();

                $user = $existingUser->fresh();
            } else {
                $user = User::query()->create([
                    'name' => $attributes['name'],
                    'email' => $invitation->email,
                    'password' => $attributes['password'],
                    'role' => $invitation->role,
                    'status' => UserStatus::ACTIVE,
                    'locale' => $locale,
                    'organization_id' => $invitation->organization_id,
                ]);
            }

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            if ($invitation->role === UserRole::MANAGER) {
                $this->activateManagerMembership($invitation, $user);
            }

            return $user;
        });
    }

    private function activateManagerMembership(OrganizationInvitation $invitation, User $manager): void
    {
        $membership = OrganizationUser::query()->updateOrCreate(
            [
                'organization_id' => $invitation->organization_id,
                'user_id' => $manager->id,
            ],
            [
                'role' => UserRole::MANAGER->value,
                'status' => ManagerMembershipStatus::ACTIVE,
                'joined_at' => now(),
                'left_at' => null,
                'is_active' => true,
                'invited_by' => $invitation->invited_by_user_id ?? $invitation->inviter_user_id,
                'invited_by_user_id' => $invitation->invited_by_user_id ?? $invitation->inviter_user_id,
                'invited_at' => $invitation->sent_at ?? $invitation->created_at ?? now(),
                'accepted_at' => now(),
                'disabled_at' => null,
            ],
        );

        $this->auditLogger->record(
            AuditLogAction::APPROVED,
            $membership,
            [
                'context' => [
                    'mutation' => 'manager.invitation_accepted',
                ],
                'manager' => [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                ],
                'invitation' => [
                    'id' => $invitation->id,
                ],
            ],
            actorUserId: $manager->id,
            description: "Manager accepted invitation: {$manager->email}",
        );

        $invitation->loadMissing(['organization', 'inviter']);

        if ($invitation->inviter instanceof User && $invitation->organization instanceof Organization) {
            $invitation->inviter->notify(
                new ManagerInvitationAcceptedNotification($manager, $invitation->organization),
            );
        }
    }
}
