<?php

namespace App\Enums;

enum PlatformNotificationSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return str($this->value)->headline()->value();
    }
}
