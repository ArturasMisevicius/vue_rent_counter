<?php

declare(strict_types=1);

namespace App\Filament\Actions\Auth;

use App\Enums\UserRole;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use EragLaravelDisposableEmail\Rules\DisposableEmailRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CreateOrganizationInvitationAction
{
    /**
     * @param  array{
     *     email: string,
     *     role: UserRole,
     *     full_name?: string|null,
     *     existing_user_id?: int|null,
     *     send_notification?: bool
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

        if (DisposableEmailRule::isDisposable((string) $attributes['email'])) {
            throw ValidationException::withMessages([
                'email' => __('validation.disposable_email', ['attribute' => __('requests.attributes.email')]),
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
            ->pendingForEmail($attributes['email'])
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation_pending_exists'),
            ]);
        }

        $plainTextToken = OrganizationInvitation::issueToken();
        $tokenHash = OrganizationInvitation::hashToken($plainTextToken);

        $invitation = DB::transaction(fn (): OrganizationInvitation => OrganizationInvitation::query()->create([
            'organization_id' => $inviter->organization_id,
            'tenant_id' => null,
            'inviter_user_id' => $inviter->id,
            'invited_by_user_id' => $inviter->id,
            'email' => $attributes['email'],
            'role' => $attributes['role'],
            'full_name' => $attributes['full_name'] ?? null,
            'token' => $tokenHash,
            'token_hash' => $tokenHash,
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'revoked_at' => null,
        ]));

        $invitation->acceptanceToken = $plainTextToken;

        if (($attributes['send_notification'] ?? true) === true) {
            Notification::route('mail', $invitation->email)
                ->notify(new OrganizationInvitationNotification($invitation, $plainTextToken));
        }

        return $invitation;
    }
}
