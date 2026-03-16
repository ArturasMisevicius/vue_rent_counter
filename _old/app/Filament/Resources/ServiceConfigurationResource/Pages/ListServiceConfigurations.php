<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceConfigurationResource\Pages;

use App\Filament\Resources\ServiceConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceConfigurations extends ListRecords
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

