<?php

namespace App\Filament\Resources\CommentReactions\Schemas;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CommentReactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('comment_id')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.comment'))
                    ->relationship('comment', 'body')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.type'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.comment_reactions.types', [
                        'like',
                        'heart',
                        'laugh',
                        'wow',
                    ]))
                    ->required(),
            ]);
    }
}
