<?php

namespace App\Enums;

enum MeterReadingValidationStatus: string
{
    case VALID = 'valid';
    case FLAGGED = 'flagged';
    case REJECTED = 'rejected';
}
