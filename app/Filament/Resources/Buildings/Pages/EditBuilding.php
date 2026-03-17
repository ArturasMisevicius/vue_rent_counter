<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Actions\Admin\Buildings\UpdateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBuilding extends EditRecord
{
    protected static string $resource = BuildingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateBuildingAction::class)->handle($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return BuildingResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
