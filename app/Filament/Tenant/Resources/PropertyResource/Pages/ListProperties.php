<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PropertyResource\Pages;

use App\Filament\Tenant\Resources\PropertyResource;
use Filament\Resources\Pages\ListRecords;

final class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}