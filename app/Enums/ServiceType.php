<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasLabel
{
    use HasTranslatableLabel;

    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case HEATING = 'heating';
}
