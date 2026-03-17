<?php

namespace App\Enums;

enum MeterReadingValidationStatus: string
{
    case PENDING = 'pending';
    case VALID = 'valid';
    case FLAGGED = 'flagged';
    case REJECTED = 'rejected';
}
