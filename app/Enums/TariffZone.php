<?php

namespace App\Enums;

enum TariffZone: string
{
    case DAY = 'day';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';
}
