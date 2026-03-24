<?php

namespace App\Filament\Resources\TimeEntries\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\TimeEntries\TimeEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeEntry extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TimeEntryResource::class;
}
