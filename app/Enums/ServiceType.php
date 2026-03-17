<?php

namespace App\Enums;

enum ServiceType: string
{
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
