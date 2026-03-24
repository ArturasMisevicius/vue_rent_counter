<?php

namespace App\Filament\Resources\CommentReactions\Pages;

use App\Filament\Resources\CommentReactions\CommentReactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommentReaction extends ViewRecord
{
    protected static string $resource = CommentReactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
