<?php

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\SystemSetting;
use App\Support\Audit\AuditLogger;

class SystemSettingObserver
{
    public function created(SystemSetting $systemSetting): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::CREATED,
            $systemSetting,
            'System setting created.',
        );
    }

    public function updated(SystemSetting $systemSetting): void
    {
        app(AuditLogger::class)->log(
            AuditLogAction::UPDATED,
            $systemSetting,
            'System setting updated.',
            [
                'changes' => $systemSetting->getChanges(),
            ],
        );
    }
}
