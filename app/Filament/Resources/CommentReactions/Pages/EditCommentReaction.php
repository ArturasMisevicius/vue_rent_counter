<?php

namespace App\Filament\Resources\CommentReactions\Pages;

use App\Filament\Resources\CommentReactions\CommentReactionResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommentReaction extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = CommentReactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
