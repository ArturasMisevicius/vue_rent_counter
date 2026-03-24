<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Resources\Buildings\BuildingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBuildings extends ListRecords
{
    protected static string $resource = BuildingResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(BuildingResource::canViewAny(), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.buildings.actions.new_building')),
        ];
    }
}
