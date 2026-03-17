<?php

namespace App\Enums;

enum SecurityViolationSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return str($this->value)->headline()->value();
    }
}
