<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeterReadingResource\Pages;

use App\Filament\Tenant\Resources\MeterReadingResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewMeterReading extends ViewRecord
{
    protected static string $resource = MeterReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}