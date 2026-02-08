<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum PropertyType: string implements HasLabel
{
    use HasTranslatableLabel;

    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case STUDIO = 'studio';
    case OFFICE = 'office';
    case RETAIL = 'retail';
    case WAREHOUSE = 'warehouse';
    case COMMERCIAL = 'commercial';

    public function getLabel(): string
    {
        return match ($this) {
            self::APARTMENT => 'Apartment',
            self::HOUSE => 'House',
            self::STUDIO => 'Studio',
            self::OFFICE => 'Office',
            self::RETAIL => 'Retail',
            self::WAREHOUSE => 'Warehouse',
            self::COMMERCIAL => 'Commercial',
        };
    }
}
