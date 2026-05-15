<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
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
                TextEntry::make('organization.name')
                    ->label(__('superadmin.comments_resource.fields.organization')),
                TextEntry::make('commentable_type')
                    ->label(__('superadmin.comments_resource.fields.commentable_type')),
                TextEntry::make('commentable_id')
                    ->label(__('superadmin.comments_resource.fields.commentable_id'))
                    ->numeric(),
                TextEntry::make('user.name')
                    ->label(__('superadmin.comments_resource.fields.user')),
                TextEntry::make('parent.id')
                    ->label(__('superadmin.comments_resource.fields.parent'))
                    ->placeholder('-'),
                TextEntry::make('body')
                    ->label(__('superadmin.comments_resource.fields.body'))
                    ->state(fn (Comment $record): ?string => app(DatabaseContentLocalizer::class)->commentBody($record->body))
                    ->columnSpanFull(),
                IconEntry::make('is_internal')
                    ->label(__('superadmin.comments_resource.fields.is_internal'))
                    ->boolean(),
                IconEntry::make('is_pinned')
                    ->label(__('superadmin.comments_resource.fields.is_pinned'))
                    ->boolean(),
                TextEntry::make('edited_at')
                    ->label(__('superadmin.comments_resource.fields.edited_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('superadmin.comments_resource.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.comments_resource.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->label(__('superadmin.comments_resource.fields.deleted_at'))
                    ->dateTime()
                    ->visible(fn (Comment $record): bool => $record->trashed()),
            ]);
    }
}
