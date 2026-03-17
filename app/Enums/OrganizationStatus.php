<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum OrganizationStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
