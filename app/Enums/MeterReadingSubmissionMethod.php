<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingSubmissionMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case ADMIN_MANUAL = 'admin_manual';
    case TENANT_PORTAL = 'tenant_portal';
    case MOBILE_APP = 'mobile_app';
    case API_INTEGRATION = 'api_integration';
    case IOT_GATEWAY = 'iot_gateway';
    case ESTIMATED = 'estimated';
    case IMPORT = 'import';
}
