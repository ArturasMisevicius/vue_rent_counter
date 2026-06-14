<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum LeadOutreachStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case RECEIVED = 'received';
    case SCHEDULED = 'scheduled';
    case DUE = 'due';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case BLOCKED = 'blocked';
}
