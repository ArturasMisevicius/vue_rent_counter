<?php

namespace App\Filament\Resources\Meters\Pages;

use App\Filament\Resources\Meters\MeterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListMeters extends ListRecords
{
    protected static string $resource = MeterResource::class;

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
                'heading' => 'You have not added any meters yet',
                'description' => 'Add meters to begin tracking utility usage and collecting tenant readings.',
                'actionLabel' => 'Add Your First Meter',
                'actionUrl' => MeterResource::getUrl('create'),
            ]));
    }
}
