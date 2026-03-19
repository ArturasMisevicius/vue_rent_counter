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
    case PEAK = 'peak';
    case OFF_PEAK = 'off_peak';
    case SHOULDER = 'shoulder';
    case HOLIDAY = 'holiday';
    case SUPER_OFF_PEAK = 'super_off_peak';
}
