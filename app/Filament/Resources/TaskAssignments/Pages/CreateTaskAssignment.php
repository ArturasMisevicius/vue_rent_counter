<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskAssignment extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = TaskAssignmentResource::class;
}
