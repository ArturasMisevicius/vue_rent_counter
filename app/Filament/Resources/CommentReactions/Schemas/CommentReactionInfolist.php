<?php

namespace App\Filament\Resources\CommentReactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommentReactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('comment.id')
                    ->label(__('superadmin.audit_logs.record_types.comment')),
                TextEntry::make('user.name')->label(__('superadmin.users.singular')),
                TextEntry::make('type'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
