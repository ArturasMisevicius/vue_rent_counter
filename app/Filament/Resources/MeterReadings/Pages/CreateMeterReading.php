<?php

namespace App\Filament\Resources\MeterReadings\Pages;

use App\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Models\Meter;
use App\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMeterReading extends CreateRecord
{
    protected static string $resource = MeterReadingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $meter = Meter::query()->findOrFail($data['meter_id']);
        $actor = app(OrganizationContext::class)->currentUser();

        return app(CreateMeterReadingAction::class)->handle(
            $meter,
            $data['reading_value'],
            $data['reading_date'],
            $actor,
            MeterReadingSubmissionMethod::from($data['submission_method']),
            $data['notes'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return MeterReadingResource::getUrl('index');
    }
}
