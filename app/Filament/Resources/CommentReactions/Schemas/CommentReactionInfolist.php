<?php

namespace App\Filament\Resources\CommentReactions\Schemas;

use App\Models\CommentReaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommentReactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('comment.id')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.comment')),
                TextEntry::make('user.name')->label(__('superadmin.relation_resources.comment_reactions.fields.user')),
                TextEntry::make('type')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.type'))
                    ->state(fn (CommentReaction $record): string => $record->typeLabel()),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
