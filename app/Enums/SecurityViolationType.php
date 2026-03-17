<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SecurityViolationType: string implements HasLabel
{
    use HasTranslatedLabel;

    case AUTHENTICATION = 'authentication';
    case RATE_LIMIT = 'rate_limit';
    case SUSPICIOUS_IP = 'suspicious_ip';
    case DATA_ACCESS = 'data_access';
}
