<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Actions\Admin\Properties\DeletePropertyAction;
use App\Actions\Admin\Properties\UpdatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdatePropertyAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record)),
        ];
    }
}
