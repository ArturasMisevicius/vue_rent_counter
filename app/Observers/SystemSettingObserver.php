<?php

namespace App\Observers;

use App\Models\SystemSetting;
use App\Support\Audit\AuditLogger;

class SystemSettingObserver
{
    public function created(SystemSetting $systemSetting): void
    {
        app(AuditLogger::class)->created($systemSetting);
    }

    public function updated(SystemSetting $systemSetting): void
    {
        app(AuditLogger::class)->updated($systemSetting);
    }

    public function deleted(SystemSetting $systemSetting): void
    {
        app(AuditLogger::class)->deleted($systemSetting);
    }
}
