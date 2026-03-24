<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Tags\TagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TagResource::class;
}
