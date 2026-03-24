<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateComment extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = CommentResource::class;
}
