<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterType: string implements HasLabel
{
    use HasTranslatedLabel;

    case WATER = 'water';
    case ELECTRICITY = 'electricity';
    case GAS = 'gas';
    case HEATING = 'heating';

    public function defaultUnit(): string
    {
        return match ($this) {
            self::WATER => 'm3',
            self::ELECTRICITY => 'kWh',
            self::GAS => 'm3',
            self::HEATING => 'kWh',
        };
    }
}
