<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TariffZone: string implements HasLabel
{
    use HasTranslatedLabel;

    case DAY = 'day';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';
}
