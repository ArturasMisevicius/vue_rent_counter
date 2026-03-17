<?php

namespace App\Observers;

use App\Filament\Support\Audit\AuditLogger;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        app(AuditLogger::class)->created($user);
    }

    public function updated(User $user): void
    {
        app(AuditLogger::class)->updated($user);
    }

    public function deleted(User $user): void
    {
        app(AuditLogger::class)->deleted($user);
    }
}
