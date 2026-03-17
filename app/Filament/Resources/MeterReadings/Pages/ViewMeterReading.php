<?php

namespace App\Filament\Resources\MeterReadings\Pages;

use App\Filament\Resources\MeterReadings\MeterReadingResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMeterReading extends ViewRecord
{
    protected static string $resource = MeterReadingResource::class;

    public function getTitle(): string
    {
        return 'Reading Details';
    }
}
