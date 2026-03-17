<?php

namespace App\Observers;

use App\Models\PlatformNotification;
use App\Support\Audit\AuditLogger;

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
