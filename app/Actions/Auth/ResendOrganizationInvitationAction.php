<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResendOrganizationInvitationAction
{
    public function __construct(
        private readonly CreateOrganizationInvitationAction $createOrganizationInvitationAction,
    ) {}

    public function handle(User $actor, OrganizationInvitation $invitation): OrganizationInvitation
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_already_active'),
            ]);
        }

        $existingUser = User::query()
            ->select(['id', 'organization_id', 'email', 'role', 'status'])
            ->where('organization_id', $invitation->organization_id)
            ->where('email', $invitation->email)
            ->where('role', $invitation->role)
            ->first();

        if ($existingUser?->status === UserStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_already_active'),
            ]);
        }

        return $this->createOrganizationInvitationAction->handle($actor, [
            'email' => $invitation->email,
            'role' => $invitation->role,
            'full_name' => $invitation->full_name,
            'existing_user_id' => $existingUser?->id,
        ]);
    }
}
