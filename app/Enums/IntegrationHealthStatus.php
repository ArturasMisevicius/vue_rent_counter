<?php

namespace App\Enums;

enum IntegrationHealthStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case FAILED = 'failed';
}
