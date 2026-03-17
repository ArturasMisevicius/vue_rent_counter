<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PropertyType: string implements HasLabel
{
    use HasTranslatedLabel;

    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case OFFICE = 'office';
    case STORAGE = 'storage';
}
