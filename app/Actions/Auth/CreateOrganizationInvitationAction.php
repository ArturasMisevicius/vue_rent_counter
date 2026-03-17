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
     * @param  array{
     *     email: string,
     *     role: UserRole,
     *     full_name?: string|null,
     *     existing_user_id?: int|null
     * }  $attributes
     */
    public function handle(User $inviter, array $attributes): OrganizationInvitation
    {
        if ((! $inviter->isAdmin() && ! $inviter->isManager()) || blank($inviter->organization_id)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_not_allowed'),
            ]);
        }

        if (! in_array($attributes['role'], [UserRole::MANAGER, UserRole::TENANT], true)) {
            throw ValidationException::withMessages([
                'role' => __('validation.in', ['attribute' => 'role']),
            ]);
        }

        if ($attributes['role'] === UserRole::MANAGER && ! $inviter->isAdmin()) {
            throw ValidationException::withMessages([
                'role' => __('validation.in', ['attribute' => 'role']),
            ]);
        }

        $existingUserId = $attributes['existing_user_id'] ?? null;
        $existingUser = null;

        if ($existingUserId !== null) {
            $existingUser = User::query()
                ->select(['id', 'organization_id', 'email', 'role'])
                ->find($existingUserId);
        }

        if (
            $existingUser !== null
            && (
                $existingUser->organization_id !== $inviter->organization_id
                || $existingUser->email !== $attributes['email']
                || $existingUser->role !== $attributes['role']
            )
        ) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_email_exists'),
            ]);
        }

        if ($existingUser === null && User::query()->where('email', $attributes['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_email_exists'),
            ]);
        }

        if (OrganizationInvitation::query()
            ->where('email', $attributes['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_pending_exists'),
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
