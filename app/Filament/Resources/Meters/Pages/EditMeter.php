<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Filament\Actions\Admin\Meters\DeleteMeterAction;
use App\Filament\Actions\Admin\Meters\UpdateMeterAction;
use App\Filament\Resources\Meters\MeterResource;
use App\Models\Meter;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMeter extends EditRecord
{
    protected static string $resource = MeterResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateMeterAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (Meter $record) => app(DeleteMeterAction::class)->handle($record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return MeterResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
