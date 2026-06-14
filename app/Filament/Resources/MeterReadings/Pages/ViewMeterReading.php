<?php

namespace App\Filament\Resources\MeterReadings\Pages;

use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;

class ViewMeterReading extends ViewRecord
{
    protected static string $resource = MeterReadingResource::class;

    public function getTitle(): string
    {
        return __('admin.meter_readings.view_title');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('admin.actions.edit')),
        ];
    }
}
