<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum LeadOutreachDirection: string implements HasLabel
{
    use HasTranslatedLabel;

    case OUTBOUND = 'outbound';
    case INBOUND = 'inbound';
    case INTERNAL_NOTE = 'internal_note';
}
