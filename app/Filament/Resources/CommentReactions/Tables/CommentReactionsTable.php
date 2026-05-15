<?php

namespace App\Filament\Resources\CommentReactions\Tables;

use App\Models\CommentReaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentReactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('comment.body')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.comment'))
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.user'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('superadmin.relation_resources.comment_reactions.fields.type'))
                    ->state(fn (CommentReaction $record): string => $record->typeLabel())
                    ->badge()
                    ->color(fn (CommentReaction $record): string => $record->typeBadgeColor())
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
