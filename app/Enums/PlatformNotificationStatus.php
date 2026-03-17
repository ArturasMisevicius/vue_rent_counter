<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PlatformNotificationStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case SENT = 'sent';
    case FAILED = 'failed';
}
