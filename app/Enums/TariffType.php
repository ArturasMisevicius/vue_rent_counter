<?php

namespace App\Enums;

enum TariffType: string
{
    case FLAT = 'flat';
    case TIME_OF_USE = 'time_of_use';
}
