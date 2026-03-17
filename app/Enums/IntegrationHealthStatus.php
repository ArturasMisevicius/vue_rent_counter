<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum IntegrationHealthStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case FAILED = 'failed';
}
