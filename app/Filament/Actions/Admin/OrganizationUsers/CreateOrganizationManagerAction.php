<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\OrganizationUsers;

use App\Enums\AuditLogAction;
use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Auth\CreateOrganizationInvitationAction;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrganizationManagerAction
{
    public function __construct(
        private readonly CreateOrganizationInvitationAction $createOrganizationInvitationAction,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     locale: string,
     *     permissions_preset?: string|null,
     *     send_invitation_email?: bool|null
     * }  $data
     */
    public function handle(Organization $organization, User $actor, array $data): OrganizationUser
    {
        $currentOrganization = $actor->currentOrganization();

        if (! $actor->isSuperadmin() && ! $currentOrganization?->is($organization)) {
            $this->recordForbiddenInviteAttempt(
                $currentOrganization ?? $organization,
                $actor,
                'cross_organization_manager_invite',
                ['target_organization_id' => $organization->id],
            );

            abort(403);
        }

        Gate::forUser($actor)->authorize('createManager', [OrganizationUser::class, $organization]);

        $email = Str::lower(trim((string) $data['email']));

        if ($email === Str::lower((string) $actor->email)) {
            $this->recordForbiddenInviteAttempt(
                $organization,
                $actor,
                'self_manager_invite',
                ['target_email' => $email],
            );

            throw ValidationException::withMessages([
                'email' => __('admin.organization_users.messages.cannot_invite_self'),
            ]);
        }

        $presetKey = $this->resolvePresetKey($data['permissions_preset'] ?? null);

        return DB::transaction(function () use ($organization, $actor, $data, $email, $presetKey): OrganizationUser {
            $manager = $this->resolveOrCreateManager($organization, $data, $email);

            $membership = OrganizationUser::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $manager->id,
                ],
                [
                    'role' => UserRole::MANAGER->value,
                    'status' => ManagerMembershipStatus::INVITED,
                    'permissions' => null,
                    'permissions_preset' => $presetKey,
                    'joined_at' => now(),
                    'left_at' => null,
                    'is_active' => false,
                    'invited_by' => $actor->id,
                    'invited_by_user_id' => $actor->id,
                    'invited_at' => now(),
                    'accepted_at' => null,
                    'disabled_at' => null,
                ],
            );

            ManagerPermission::syncForManager(
                $manager,
                $organization,
                ManagerPermissionCatalog::presets()[$presetKey]['matrix'],
            );

            $invitation = $this->issueInvitation(
                actor: $actor,
                organization: $organization,
                manager: $manager,
                sendNotification: (bool) ($data['send_invitation_email'] ?? true),
            );

            $this->auditLogger->record(
                AuditLogAction::SENT,
                $membership,
                [
                    'context' => [
                        'mutation' => 'manager.invited',
                        'actor_type' => $actor->isSuperadmin() ? 'superadmin' : 'organization_admin',
                    ],
                    'manager' => [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                    ],
                    'invitation' => [
                        'id' => $invitation->id,
                        'expires_at' => $invitation->expires_at?->toISOString(),
                    ],
                    'permissions_preset' => $presetKey,
                ],
                actorUserId: $actor->id,
                description: "Manager invited: {$manager->email}",
            );

            return $membership->fresh(['organization', 'user', 'inviter', 'invitedBy']);
        });
    }

    /**
     * @param  array{name: string, locale: string}  $data
     */
    private function resolveOrCreateManager(Organization $organization, array $data, string $email): User
    {
        $existingUser = User::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'locale',
            ])
            ->where('email', $email)
            ->first();

        if ($existingUser instanceof User) {
            $this->ensureExistingUserCanBeInvited($existingUser, $organization);

            $existingUser->forceFill([
                'name' => $data['name'],
                'locale' => $data['locale'],
                'status' => UserStatus::INACTIVE,
            ])->save();

            return $existingUser->fresh();
        }

        return $organization->users()->create([
            'role' => UserRole::MANAGER,
            'name' => $data['name'],
            'email' => $email,
            'status' => UserStatus::INACTIVE,
            'locale' => $data['locale'],
            'password' => Str::random(32),
        ]);
    }

    private function ensureExistingUserCanBeInvited(User $user, Organization $organization): void
    {
        if ($user->organization_id !== $organization->id || $user->role !== UserRole::MANAGER) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_email_exists'),
            ]);
        }

        if ($user->status === UserStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_already_active'),
            ]);
        }
    }

    private function issueInvitation(
        User $actor,
        Organization $organization,
        User $manager,
        bool $sendNotification,
    ): OrganizationInvitation {
        $inviter = $actor->isSuperadmin()
            ? $this->resolveInviterForSuperadmin($organization)
            : $actor;

        return $this->createOrganizationInvitationAction->handle($inviter, [
            'email' => $manager->email,
            'role' => UserRole::MANAGER,
            'full_name' => $manager->name,
            'existing_user_id' => $manager->id,
            'send_notification' => $sendNotification,
        ]);
    }

    private function resolveInviterForSuperadmin(Organization $organization): User
    {
        $organization->loadMissing([
            'owner:id,organization_id,name,email,role,status',
        ]);

        $inviter = $organization->owner;

        if (! $inviter instanceof User || ! $inviter->isAdminLike()) {
            $inviter = $organization->users()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                ->adminLike()
                ->orderedByName()
                ->first();
        }

        if (! $inviter instanceof User) {
            throw ValidationException::withMessages([
                'organization_id' => __('superadmin.organizations.messages.no_primary_admin'),
            ]);
        }

        return $inviter;
    }

    private function resolvePresetKey(?string $preset): string
    {
        $presetKey = filled($preset) ? (string) $preset : 'read_only';

        if (! array_key_exists($presetKey, ManagerPermissionCatalog::presets())) {
            throw ValidationException::withMessages([
                'permissions_preset' => __('validation.in', ['attribute' => __('admin.organization_users.fields.permissions_preset')]),
            ]);
        }

        return $presetKey;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordForbiddenInviteAttempt(
        Organization $organization,
        User $actor,
        string $reason,
        array $metadata = [],
    ): void {
        $this->auditLogger->record(
            AuditLogAction::REJECTED,
            $organization,
            [
                'context' => [
                    'mutation' => 'manager.forbidden_access_attempt',
                    'reason' => $reason,
                    'actor_type' => $actor->isSuperadmin() ? 'superadmin' : 'organization_user',
                ],
                ...$metadata,
            ],
            actorUserId: $actor->id,
            description: "Forbidden manager invite attempt: {$actor->email}",
        );
    }
}
