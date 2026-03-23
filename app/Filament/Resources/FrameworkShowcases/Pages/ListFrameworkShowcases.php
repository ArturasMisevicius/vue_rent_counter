<?php

namespace App\Filament\Resources\FrameworkShowcases\Pages;

use App\Filament\Resources\FrameworkShowcases\FrameworkShowcaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFrameworkShowcases extends ListRecords
{
    protected static string $resource = FrameworkShowcaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
