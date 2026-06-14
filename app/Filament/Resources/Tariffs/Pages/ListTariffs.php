<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\Tariffs\TariffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTariffs extends ListRecords
{
    protected static string $resource = TariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('tariffs.index'),
            CreateAction::make()
                ->label(__('admin.tariffs.actions.new_tariff')),
        ];
    }
}
