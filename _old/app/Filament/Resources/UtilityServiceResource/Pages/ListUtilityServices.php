<?php

declare(strict_types=1);

namespace App\Filament\Resources\UtilityServiceResource\Pages;

use App\Filament\Resources\UtilityServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUtilityServices extends ListRecords
{
    protected static string $resource = UtilityServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

