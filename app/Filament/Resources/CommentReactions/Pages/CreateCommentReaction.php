<?php

namespace App\Filament\Resources\CommentReactions\Pages;

use App\Filament\Resources\CommentReactions\CommentReactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommentReaction extends CreateRecord
{
    protected static string $resource = CommentReactionResource::class;
}
