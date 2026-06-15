<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\UserRole;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\TenantInvitationResentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ResendTenantInvitation
{
    public function __construct(
        private readonly SendTenantInvitation $sendTenantInvitation,
    ) {}

    public function handle(
        User $actor,
        User|OrganizationInvitation $target,
        int $expirationDays = 7,
        bool $sendEmail = true,
    ): OrganizationInvitation {
        $tenant = $target instanceof User
            ? $target
            : $this->tenantForInvitation($target);

        $invitation = $this->sendTenantInvitation->handle(
            $actor,
            $tenant,
            $expirationDays,
            false,
            'tenant_invitation.resent',
            $sendEmail ? 'email' : 'manual_link',
        );

        if ($sendEmail) {
            Notification::route('mail', $invitation->email)
                ->notify(new TenantInvitationResentNotification($invitation, $invitation->acceptanceToken));
        }

        return $invitation;
    }

    private function tenantForInvitation(OrganizationInvitation $invitation): User
    {
        if ($invitation->tenant instanceof User) {
            return $invitation->tenant;
        }

        $tenant = User::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'tenant_status',
                'portal_access_enabled',
                'locale',
                'created_at',
                'updated_at',
            ])
            ->when(
                $invitation->tenant_id !== null,
                fn ($query) => $query->whereKey($invitation->tenant_id),
                fn ($query) => $query
                    ->where('organization_id', $invitation->organization_id)
                    ->where('email', $invitation->email)
                    ->where('role', UserRole::TENANT),
            )
            ->first();

        if (! $tenant instanceof User) {
            throw ValidationException::withMessages([
                'tenant' => __('auth.invitation_tenant_missing'),
            ]);
        }

        return $tenant;
    }
}
