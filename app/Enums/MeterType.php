<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterType: string implements HasLabel
{
    use HasTranslatedLabel;

    case WATER = 'water';
    case WATER_COLD = 'water_cold';
    case WATER_HOT = 'water_hot';
    case ELECTRICITY = 'electricity';
    case GAS = 'gas';
    case HEATING = 'heating';
    case COOLING = 'cooling';
    case STEAM = 'steam';
    case SOLAR = 'solar';
    case CUSTOM = 'custom';

    public function defaultUnit(): UnitOfMeasurement
    {
        return match ($this) {
            self::WATER,
            self::WATER_COLD,
            self::WATER_HOT,
            self::GAS => UnitOfMeasurement::CUBIC_METER,
            self::ELECTRICITY,
            self::HEATING,
            self::COOLING,
            self::SOLAR => UnitOfMeasurement::KILOWATT_HOUR,
            self::STEAM => UnitOfMeasurement::MEGAWATT_HOUR,
            self::CUSTOM => UnitOfMeasurement::UNIT,
        };
    }
}
