<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBuilding extends ViewRecord
{
    protected static string $resource = BuildingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->using(fn (Building $record) => app(DeleteBuildingAction::class)->handle($record)),
        ];
    }
}
