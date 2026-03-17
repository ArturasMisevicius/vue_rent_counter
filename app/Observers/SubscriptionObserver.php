<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\Subscription;
use App\Support\Audit\AuditLogger;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::CREATED,
            $subscription,
            'Subscription created.',
        );
    }

    public function updated(Subscription $subscription): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::UPDATED,
            $subscription,
            'Subscription updated.',
            [
                'changes' => $subscription->getChanges(),
            ],
        );
    }
}
