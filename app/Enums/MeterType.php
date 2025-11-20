<?php

namespace App\Enums;

enum MeterType: string
{
    case ELECTRICITY = 'electricity';
    case WATER_COLD = 'water_cold';
    case WATER_HOT = 'water_hot';
    case HEATING = 'heating';
}
