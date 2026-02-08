<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterType: string implements HasLabel
{
    use HasTranslatableLabel;

    case ELECTRICITY = 'electricity';
    case WATER_COLD = 'water_cold';
    case WATER_HOT = 'water_hot';
    case HEATING = 'heating';
    case CUSTOM = 'custom';
}
