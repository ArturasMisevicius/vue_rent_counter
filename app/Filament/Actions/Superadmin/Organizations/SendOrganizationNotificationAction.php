<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Jobs\Superadmin\Organizations\SendOrganizationAnnouncementJob;
use App\Models\Organization;

class SendOrganizationNotificationAction
{
    public function handle(Organization $organization, string $title, string $body, string $severity): void
    {
        SendOrganizationAnnouncementJob::dispatch(
            $organization->id,
            $title,
            $body,
            $severity,
        );
    }
}
