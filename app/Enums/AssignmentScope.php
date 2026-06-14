<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum AssignmentScope: string implements HasLabel
{
    use HasTranslatedLabel;

    case PROPERTY = 'property';
    case BUILDING = 'building';
    case ORGANIZATION = 'organization';
    case TENANT = 'tenant';
}
