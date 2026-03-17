<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Actions\Admin\Buildings\CreateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBuilding extends CreateRecord
{
    protected static string $resource = BuildingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBuildingAction::class)->handle(auth()->user()->organization, $data);
    }
}
