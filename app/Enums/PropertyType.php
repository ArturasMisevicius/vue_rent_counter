<?php

namespace App\Enums;

enum PropertyType: string
{
    case APARTMENT = 'apartment';
    case HOUSE = 'house';
    case OFFICE = 'office';
    case STORAGE = 'storage';
}
