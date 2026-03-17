<?php

namespace App\Actions\Auth;

use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResendOrganizationInvitationAction
{
    public function handle(User $inviter, OrganizationInvitation $invitation): OrganizationInvitation
    {
        if (
            ! $inviter->isAdmin() ||
            blank($inviter->organization_id) ||
            $inviter->organization_id !== $invitation->organization_id
        ) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_not_allowed'),
            ]);
        }

        if (
            $invitation->isAccepted() ||
            User::query()->where('email', $invitation->email)->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_email_exists'),
            ]);
        }

        $resentInvitation = DB::transaction(function () use ($inviter, $invitation): OrganizationInvitation {
            if ($invitation->isPending()) {
                $invitation->forceFill([
                    'expires_at' => now(),
                ])->save();
            }

            return OrganizationInvitation::query()->create([
                'organization_id' => $invitation->organization_id,
                'inviter_user_id' => $inviter->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'full_name' => $invitation->full_name,
                'token' => (string) Str::uuid(),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ]);
        });

        Notification::route('mail', $resentInvitation->email)
            ->notify(new OrganizationInvitationNotification($resentInvitation));

        return $resentInvitation;
    }
}
