<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum LeadOutreachChannel: string implements HasLabel
{
    use HasTranslatedLabel;

    case EMAIL = 'email';
    case PHONE = 'phone';
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';
    case MANUAL = 'manual';
    case OTHER = 'other';
}
