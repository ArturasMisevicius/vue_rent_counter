<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SystemSettingCategory: string implements HasLabel
{
    use HasTranslatedLabel;

    case GENERAL = 'general';
    case BILLING = 'billing';
    case LOCALIZATION = 'localization';
    case SECURITY = 'security';
    case INTEGRATIONS = 'integrations';
    case NOTIFICATIONS = 'notifications';
    case EMAIL = 'email';
    case SUBSCRIPTION = 'subscription';
    case BACKUPS = 'backups';
    case MAINTENANCE = 'maintenance';
    case REPORTING = 'reporting';
    case API = 'api';
    case COMPLIANCE = 'compliance';
}
