<?php

namespace App\Filament\Resources\SecurityViolations\Pages;

use App\Filament\Resources\SecurityViolations\SecurityViolationResource;
use Filament\Resources\Pages\ListRecords;

class ListSecurityViolations extends ListRecords
{
    protected static string $resource = SecurityViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
