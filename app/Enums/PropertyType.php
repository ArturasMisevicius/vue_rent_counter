<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum PropertyType: string implements HasLabel
{
    use HasTranslatableLabel;

    case APARTMENT = 'apartment';
    case HOUSE = 'house';
}
