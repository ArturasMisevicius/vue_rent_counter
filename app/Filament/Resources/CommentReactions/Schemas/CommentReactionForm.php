<?php

namespace App\Filament\Resources\CommentReactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CommentReactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('comment_id')
                    ->relationship('comment', 'body')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('type')
                    ->required(),
            ]);
    }
}
