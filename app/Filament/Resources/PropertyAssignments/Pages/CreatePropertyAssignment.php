<?php

namespace App\Filament\Resources\PropertyAssignments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\PropertyAssignments\PropertyAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePropertyAssignment extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = PropertyAssignmentResource::class;
}
