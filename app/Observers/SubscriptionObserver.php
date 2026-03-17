<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Support\Audit\AuditLogger;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        app(AuditLogger::class)->created($subscription);
    }

    public function updated(Subscription $subscription): void
    {
        app(AuditLogger::class)->updated($subscription);
    }

    public function deleted(Subscription $subscription): void
    {
        app(AuditLogger::class)->deleted($subscription);
    }
}
