<?php

namespace App\Observers;

use App\Filament\Support\Audit\AuditLogger;
use App\Models\PlatformNotification;

class PlatformNotificationObserver
{
    public function created(PlatformNotification $platformNotification): void
    {
        app(AuditLogger::class)->created($platformNotification);
    }

    public function updated(PlatformNotification $platformNotification): void
    {
        app(AuditLogger::class)->updated($platformNotification);
    }

    public function deleted(PlatformNotification $platformNotification): void
    {
        app(AuditLogger::class)->deleted($platformNotification);
    }
}
