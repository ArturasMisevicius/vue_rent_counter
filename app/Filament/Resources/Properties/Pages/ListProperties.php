<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

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
                'heading' => 'You have not added any properties yet',
                'description' => 'Add properties so you can assign tenants, connect meters, and issue invoices.',
                'actionLabel' => 'Add Your First Property',
                'actionUrl' => PropertyResource::getUrl('create'),
            ]));
    }
}
