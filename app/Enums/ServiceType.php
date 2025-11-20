<?php

namespace App\Enums;

enum ServiceType: string
{
    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case HEATING = 'heating';
}
