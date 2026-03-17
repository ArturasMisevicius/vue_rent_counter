<?php

namespace App\Enums;

enum SecurityViolationSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
