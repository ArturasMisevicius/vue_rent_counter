<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\Organization;
use App\Support\Audit\AuditLogger;

class OrganizationObserver
{
    public function created(Organization $organization): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::CREATED,
            $organization,
            'Organization created.',
        );
    }

    public function updated(Organization $organization): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::UPDATED,
            $organization,
            'Organization updated.',
            [
                'changes' => $organization->getChanges(),
            ],
        );
    }
}
