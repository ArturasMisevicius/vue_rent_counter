<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum AuditLogAction: string implements HasLabel
{
    use HasTranslatedLabel;

    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case SUSPENDED = 'suspended';
    case REINSTATED = 'reinstated';
    case SENT = 'sent';
}
