<?php

namespace App\Observers;

use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;

class OrganizationObserver
{
    public function created(Organization $organization): void
    {
        app(AuditLogger::class)->created($organization);
    }

    public function updated(Organization $organization): void
    {
        app(AuditLogger::class)->updated($organization);
    }

    public function deleted(Organization $organization): void
    {
        app(AuditLogger::class)->deleted($organization);
    }
}
