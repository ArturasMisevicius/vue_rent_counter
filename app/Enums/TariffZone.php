<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum TariffZone: string implements HasLabel
{
    use HasTranslatableLabel;

    case DAY = 'day';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';
}
