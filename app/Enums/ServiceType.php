<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasLabel
{
    use HasTranslatedLabel;

    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case HEATING = 'heating';
    case GAS = 'gas';

    public function defaultUnit(): string
    {
        return match ($this) {
            self::ELECTRICITY, self::HEATING => 'kWh',
            self::WATER, self::GAS => 'm3',
        };
    }
}
