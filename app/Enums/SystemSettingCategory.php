<?php

namespace App\Enums;

enum SystemSettingCategory: string
{
    case GENERAL = 'general';
    case BILLING = 'billing';
    case LOCALIZATION = 'localization';
    case SECURITY = 'security';
    case INTEGRATIONS = 'integrations';
    case NOTIFICATIONS = 'notifications';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::BILLING => 'Billing',
            self::LOCALIZATION => 'Localization',
            self::SECURITY => 'Security',
            self::INTEGRATIONS => 'Integrations',
            self::NOTIFICATIONS => 'Notifications',
        };
    }
}
