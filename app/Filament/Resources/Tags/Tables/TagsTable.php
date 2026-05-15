<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Models\Organization;
use App\Models\Tag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')->label(__('superadmin.organizations.singular'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('superadmin.relation_resources.tags.fields.name'))
                    ->state(fn (Tag $record): string => $record->displayName())
                    ->searchable(),
                TextColumn::make('color')
                    ->label(__('superadmin.relation_resources.tags.fields.color'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('superadmin.relation_resources.tags.fields.type'))
                    ->state(fn (Tag $record): string => $record->typeLabel())
                    ->searchable(),
                IconColumn::make('is_system')
                    ->label(__('superadmin.relation_resources.tags.fields.is_system'))
                    ->boolean(),
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
                SelectFilter::make('organization')->label(__('superadmin.organizations.singular'))
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
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
