<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ProjectType: string implements HasLabel
{
    use HasTranslatedLabel;

    case MAINTENANCE = 'maintenance';
    case RENOVATION = 'renovation';
    case INSPECTION = 'inspection';
    case COMPLIANCE = 'compliance';
    case INSTALLATION = 'installation';
    case CLEANING = 'cleaning';
    case EMERGENCY = 'emergency';
    case ADMINISTRATIVE = 'administrative';
    case OTHER = 'other';

    public function isEmergency(): bool
    {
        return $this === self::EMERGENCY;
    }
}
