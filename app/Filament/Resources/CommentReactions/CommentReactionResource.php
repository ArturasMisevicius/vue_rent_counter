<?php

namespace App\Filament\Resources\CommentReactions;

use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\CommentReactions\Pages\CreateCommentReaction;
use App\Filament\Resources\CommentReactions\Pages\EditCommentReaction;
use App\Filament\Resources\CommentReactions\Pages\ListCommentReactions;
use App\Filament\Resources\CommentReactions\Pages\ViewCommentReaction;
use App\Filament\Resources\CommentReactions\Schemas\CommentReactionForm;
use App\Filament\Resources\CommentReactions\Schemas\CommentReactionInfolist;
use App\Filament\Resources\CommentReactions\Tables\CommentReactionsTable;
use App\Models\CommentReaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommentReactionResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static ?string $model = CommentReaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CommentReactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommentReactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommentReactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommentReactions::route('/'),
            'create' => CreateCommentReaction::route('/create'),
            'view' => ViewCommentReaction::route('/{record}'),
            'edit' => EditCommentReaction::route('/{record}/edit'),
        ];
    }
}
