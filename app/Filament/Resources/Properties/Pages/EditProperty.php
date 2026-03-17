<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Actions\Admin\Properties\UpdatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdatePropertyAction::class)->handle($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return PropertyResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
