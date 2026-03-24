<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum UnitOfMeasurement: string implements HasLabel
{
    use HasTranslatedLabel;

    case CUBIC_METER = 'm3';
    case KILOWATT_HOUR = 'kWh';
    case MEGAWATT_HOUR = 'MWh';
    case DAY = 'day';
    case MONTH = 'month';
    case COLLECTION = 'collection';
    case UNIT = 'unit';
}
