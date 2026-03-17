<?php

namespace App\Filament\Resources\Buildings\Pages;

use App\Filament\Resources\Buildings\BuildingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListBuildings extends ListRecords
{
    protected static string $resource = BuildingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyState(view('components.ui.empty-state', [
                'heading' => 'You have not added any buildings yet',
                'description' => 'Add your first building to start organizing properties, tenants, and billing.',
                'actionLabel' => 'Add Your First Building',
                'actionUrl' => BuildingResource::getUrl('create'),
            ]));
    }
}
