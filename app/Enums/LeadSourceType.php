<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum LeadSourceType: string implements HasLabel
{
    use HasTranslatedLabel;

    case ARUODAS_CSV = 'aruodas_csv';
    case MANUAL_IMPORT = 'manual_import';
    case PARTNER_LIST = 'partner_list';
    case WEBSITE_FORM = 'website_form';
    case PHONE_INQUIRY = 'phone_inquiry';
    case OTHER = 'other';
}
