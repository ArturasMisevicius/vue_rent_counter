<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ManagerMembershipStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case INVITED = 'invited';
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
    case EXPIRED = 'expired';

    public function permitsAccess(): bool
    {
        return $this === self::ACTIVE;
    }
}
