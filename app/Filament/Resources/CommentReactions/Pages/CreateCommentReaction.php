<?php

namespace App\Filament\Resources\CommentReactions\Pages;

use App\Filament\Resources\CommentReactions\CommentReactionResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Resources\Pages\CreateRecord;

class CreateCommentReaction extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = CommentReactionResource::class;
}
