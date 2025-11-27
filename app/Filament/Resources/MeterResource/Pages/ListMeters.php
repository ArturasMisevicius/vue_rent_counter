<?php

declare(strict_types=1);

namespace App\Filament\Resources\MeterResource\Pages;

use App\Filament\Resources\MeterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeters extends ListRecords
{
    protected static string $resource = MeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('meters.actions.create'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
