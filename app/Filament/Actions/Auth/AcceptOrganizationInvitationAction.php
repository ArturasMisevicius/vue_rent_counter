<?php

namespace App\Filament\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptOrganizationInvitationAction
{
    /**
     * @param  array{name: string, password: string}  $attributes
     */
    public function handle(OrganizationInvitation $invitation, array $attributes, string $locale): User
    {
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

            return $user;
        });
    }
}
