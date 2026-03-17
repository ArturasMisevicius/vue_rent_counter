<?php

namespace App\Enums;

enum IntegrationHealthStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case FAILED = 'failed';

    public function label(): string
    {
        return str($this->value)->headline()->value();
    }
}
