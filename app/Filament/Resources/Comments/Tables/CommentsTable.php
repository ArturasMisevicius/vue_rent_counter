<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Comment;
use App\Models\Organization;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.comments_resource.fields.organization'))
                    ->searchable(),
                TextColumn::make('commentable_type')
                    ->label(__('superadmin.comments_resource.fields.commentable_type'))
                    ->state(fn (Comment $record): string => $record->commentableTypeLabel())
                    ->searchable(),
                TextColumn::make('commentable_id')
                    ->label(__('superadmin.comments_resource.fields.commentable_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('superadmin.comments_resource.fields.user'))
                    ->searchable(),
                TextColumn::make('parent.body')
                    ->label(__('superadmin.comments_resource.fields.parent_comment'))
                    ->state(fn (Comment $record): ?string => app(DatabaseContentLocalizer::class)->commentBody($record->parent?->body))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('body')
                    ->label(__('superadmin.comments_resource.fields.body'))
                    ->state(fn (Comment $record): ?string => app(DatabaseContentLocalizer::class)->commentBody($record->body))
                    ->limit(70)
                    ->searchable(),
                IconColumn::make('is_internal')
                    ->label(__('superadmin.comments_resource.fields.is_internal'))
                    ->boolean(),
                IconColumn::make('is_pinned')
                    ->label(__('superadmin.comments_resource.fields.is_pinned'))
                    ->boolean(),
                TextColumn::make('edited_at')
                    ->label(__('superadmin.comments_resource.fields.edited_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.comments_resource.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.comments_resource.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('superadmin.comments_resource.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.comments_resource.fields.organization'))
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
