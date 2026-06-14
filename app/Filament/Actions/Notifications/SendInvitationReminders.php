<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Enums\UserRole;
use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SendInvitationReminders
{
    public function __construct(
        private readonly NotifyTenant $notifyTenant,
        private readonly NotifyOrganizationAdmins $notifyOrganizationAdmins,
    ) {}

    public function handle(?Organization $organization = null, int $expiringWithinDays = 2): int
    {
        return $this->sendPendingReminders($organization, $expiringWithinDays)
            + $this->sendExpiredNotifications($organization);
    }

    private function sendPendingReminders(?Organization $organization, int $expiringWithinDays): int
    {
        $sent = 0;

        OrganizationInvitation::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'inviter_user_id',
                'invited_by_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'token_hash',
                'sent_at',
                'expires_at',
                'accepted_at',
                'revoked_at',
                'created_at',
                'updated_at',
            ])
            ->pending()
            ->whereDate('expires_at', '<=', today()->addDays($expiringWithinDays))
            ->when(
                $organization instanceof Organization,
                fn (Builder $query): Builder => $query->forOrganization($organization->id),
            )
            ->with(['tenant:id,organization_id,name,email,role,status'])
            ->chunkById(100, function (Collection $invitations) use (&$sent, $expiringWithinDays): void {
                foreach ($invitations as $invitation) {
                    if (! $invitation instanceof OrganizationInvitation || ! $invitation->tenant instanceof User) {
                        continue;
                    }

                    $notification = $this->notifyTenant->handle(
                        tenant: $invitation->tenant,
                        type: DomainNotificationCatalog::TENANT_INVITATION_SENT,
                        subject: $invitation,
                        data: [
                            'days' => $expiringWithinDays,
                            'milestone' => 'expiring_soon',
                        ],
                    );

                    if ($notification !== null && $notification->wasRecentlyCreated) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    private function sendExpiredNotifications(?Organization $organization): int
    {
        $sent = 0;
        $organizations = [];

        OrganizationInvitation::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'inviter_user_id',
                'invited_by_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'token_hash',
                'sent_at',
                'expires_at',
                'accepted_at',
                'revoked_at',
                'created_at',
                'updated_at',
            ])
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('role', UserRole::TENANT)
            ->whereDate('expires_at', '<', today())
            ->when(
                $organization instanceof Organization,
                fn (Builder $query): Builder => $query->forOrganization($organization->id),
            )
            ->chunkById(100, function (Collection $invitations) use (&$sent, &$organizations): void {
                foreach ($invitations as $invitation) {
                    if (! $invitation instanceof OrganizationInvitation) {
                        continue;
                    }

                    $organization = $organizations[$invitation->organization_id] ??= Organization::query()
                        ->select(['id', 'name', 'owner_user_id'])
                        ->find($invitation->organization_id);

                    if (! $organization instanceof Organization) {
                        continue;
                    }

                    $sent += $this->notifyOrganizationAdmins
                        ->handle(
                            organization: $organization,
                            type: DomainNotificationCatalog::TENANT_INVITATION_EXPIRED,
                            subject: $invitation,
                            data: ['milestone' => 'expired'],
                        )
                        ->filter->wasRecentlyCreated
                        ->count();
                }
            });

        return $sent;
    }
}
