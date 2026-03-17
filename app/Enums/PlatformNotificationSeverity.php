<?php

namespace App\Enums;

enum PlatformNotificationSeverity: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
}
