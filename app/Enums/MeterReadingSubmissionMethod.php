<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingSubmissionMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case ADMIN_MANUAL = 'admin_manual';
    case TENANT_PORTAL = 'tenant_portal';
    case IMPORT = 'import';
}
