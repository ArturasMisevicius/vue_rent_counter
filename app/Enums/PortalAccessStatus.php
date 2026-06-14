<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PortalAccessStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case NOT_INVITED = 'not_invited';
    case INVITED = 'invited';
    case INVITATION_EXPIRED = 'invitation_expired';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
}
