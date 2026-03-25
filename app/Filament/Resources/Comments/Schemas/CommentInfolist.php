<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Models\Comment;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('commentable_type'),
                TextEntry::make('commentable_id')
                    ->numeric(),
                TextEntry::make('user.name')->label(__('superadmin.users.singular')),
                TextEntry::make('parent.id')
                    ->label(__('superadmin.comments_resource.fields.parent'))
                    ->placeholder('-'),
                TextEntry::make('body')
                    ->columnSpanFull(),
                IconEntry::make('is_internal')
                    ->boolean(),
                IconEntry::make('is_pinned')
                    ->boolean(),
                TextEntry::make('edited_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Comment $record): bool => $record->trashed()),
            ]);
    }
}
