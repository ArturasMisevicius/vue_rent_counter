<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Enums\PlatformNotificationStatus;
use App\Models\PlatformNotification;
use App\Support\Audit\AuditLogger;

class PlatformNotificationObserver
{
    public function created(PlatformNotification $platformNotification): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::CREATED,
            $platformNotification,
            'Platform notification created.',
        );
    }

    public function updated(PlatformNotification $platformNotification): void
    {
        $action = $platformNotification->status === PlatformNotificationStatus::SENT
            && $platformNotification->wasChanged('status')
            ? AuditLogAction::SENT
            : AuditLogAction::UPDATED;

        app(AuditLogger::class)->log(
            $action,
            $platformNotification,
            'Platform notification updated.',
            [
                'changes' => $platformNotification->getChanges(),
            ],
        );
    }
}
