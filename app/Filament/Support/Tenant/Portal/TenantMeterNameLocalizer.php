<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenant\Portal;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Meter;

class TenantMeterNameLocalizer
{
    public function __construct(
        private readonly DatabaseContentLocalizer $databaseContentLocalizer,
    ) {}

    public function displayName(?Meter $meter): string
    {
        if (! $meter instanceof Meter) {
            return __('dashboard.not_available');
        }

        return $this->databaseContentLocalizer->meterName($meter->name, $meter->type);
    }
}
