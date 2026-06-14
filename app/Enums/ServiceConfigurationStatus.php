<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ServiceConfigurationStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
    case CONFIGURATION_ERROR = 'configuration_error';

    public function canBeUsedForBilling(): bool
    {
        return $this === self::ACTIVE;
    }
}
