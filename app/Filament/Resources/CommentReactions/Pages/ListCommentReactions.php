<?php

namespace App\Filament\Resources\CommentReactions\Pages;

use App\Filament\Resources\CommentReactions\CommentReactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommentReactions extends ListRecords
{
    protected static string $resource = CommentReactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
