<?php

namespace App\Filament\Resources\MeterReadings\Pages;

use App\Actions\Admin\MeterReadings\UpdateMeterReadingAction;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMeterReading extends EditRecord
{
    protected static string $resource = MeterReadingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateMeterReadingAction::class)->handle($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return MeterReadingResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
