<?php

namespace App\Enums;

enum SystemSettingCategory: string
{
    case GENERAL = 'general';
    case BILLING = 'billing';
    case LOCALIZATION = 'localization';
    case SECURITY = 'security';
    case INTEGRATIONS = 'integrations';
}
