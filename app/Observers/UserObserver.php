<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\User;
use App\Support\Audit\AuditLogger;

class UserObserver
{
    public function created(User $user): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::CREATED,
            $user,
            'User created.',
        );
    }

    public function updated(User $user): void
    {
        $changes = collect($user->getChanges())
            ->except([
                'last_login_at',
                'remember_token',
                'updated_at',
            ])
            ->all();

        if ($changes === []) {
            return;
        }

        app(AuditLogger::class)->log(
            AuditLogAction::UPDATED,
            $user,
            'User updated.',
            [
                'changes' => $changes,
            ],
        );
    }
}
