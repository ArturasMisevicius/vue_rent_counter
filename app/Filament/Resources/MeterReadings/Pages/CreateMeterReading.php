<?php

namespace App\Filament\Resources\MeterReadings\Pages;

use App\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Models\Meter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMeterReading extends CreateRecord
{
    protected static string $resource = MeterReadingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $meter = Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name'])
            ->findOrFail($data['meter_id']);

        return app(CreateMeterReadingAction::class)->handle(
            $meter,
            $data['reading_value'],
            $data['reading_date'],
            auth()->user(),
            MeterReadingSubmissionMethod::from($data['submission_method']),
            $data['notes'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return MeterReadingResource::getUrl('index');
    }
}
