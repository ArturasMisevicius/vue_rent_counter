<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PropertyType: string implements HasLabel
{
    use HasTranslatedLabel;

    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case STUDIO = 'studio';
    case OFFICE = 'office';
    case RETAIL = 'retail';
    case WAREHOUSE = 'warehouse';
    case COMMERCIAL = 'commercial';
    case INDUSTRIAL = 'industrial';
    case MIXED_USE = 'mixed_use';
    case GARAGE = 'garage';
    case PARKING = 'parking';
    case STORAGE = 'storage';
    case OTHER = 'other';
}
