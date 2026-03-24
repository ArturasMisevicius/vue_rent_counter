<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TaskResource::class;
}
