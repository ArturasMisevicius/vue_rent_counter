<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Models\Organization;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationBroadcastNotification;

class SendOrganizationNotificationAction
{
    public function handle(Organization $organization, string $title, string $body, string $severity): void
    {
        User::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'last_login_at',
                'created_at',
                'updated_at',
                'password',
                'remember_token',
            ])
            ->forOrganization($organization->id)
            ->chunkById(100, function ($users) use ($organization, $title, $body, $severity): void {
                foreach ($users as $user) {
                    $user->notify(new OrganizationBroadcastNotification($organization, $title, $body, $severity));
                }
            });
    }
}
