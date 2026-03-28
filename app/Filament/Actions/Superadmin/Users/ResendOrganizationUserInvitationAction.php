<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Users;

use App\Filament\Actions\Auth\ResendOrganizationInvitationAction;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ResendOrganizationUserInvitationAction
{
    public function __construct(
        private readonly ResendOrganizationInvitationAction $resendOrganizationInvitationAction,
    ) {}

    public function handle(Organization $organization, User $user): OrganizationInvitation
    {
        $invitation = $user->latestResendableOrganizationInvitation();

        if (! $invitation instanceof OrganizationInvitation) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_pending_exists'),
            ]);
        }

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
                'email' => __('superadmin.organizations.messages.no_primary_admin'),
            ]);
        }

        return $this->resendOrganizationInvitationAction->handle($inviter, $invitation);
    }
}
