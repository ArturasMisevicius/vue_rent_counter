<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Actions\Admin\Buildings\UpdateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBuilding extends EditRecord
{
    protected static string $resource = BuildingResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateBuildingAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (Building $record) => app(DeleteBuildingAction::class)->handle($record)),
        ];
    }
}
