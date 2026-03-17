<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrganizationInvitationAction
{
    /**
     * @param  array{email: string, role: UserRole, full_name?: string|null}  $attributes
     */
    public function handle(User $inviter, array $attributes): OrganizationInvitation
    {
        if (! $inviter->isAdmin() || blank($inviter->organization_id)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_not_allowed'),
            ]);
        }

        if (! in_array($attributes['role'], [UserRole::MANAGER, UserRole::TENANT], true)) {
            throw ValidationException::withMessages([
                'role' => __('validation.in', ['attribute' => 'role']),
            ]);
        }

        if (User::query()->where('email', $attributes['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_email_exists'),
            ]);
        }

        $invitation = OrganizationInvitation::query()->create([
            'organization_id' => $inviter->organization_id,
            'inviter_user_id' => $inviter->id,
            'email' => $attributes['email'],
            'role' => $attributes['role'],
            'full_name' => $attributes['full_name'] ?? null,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification($invitation));

        return $invitation;
    }
}
