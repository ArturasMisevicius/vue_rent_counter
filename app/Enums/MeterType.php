<?php

namespace App\Enums;

enum MeterType: string
{
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
