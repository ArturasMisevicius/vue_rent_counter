<?php

namespace App\Filament\Resources\SubscriptionRenewals\Tables;

use App\Models\Organization;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionRenewalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription.plan')
                    ->label(__('superadmin.subscriptions_resource.fields.plan'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('subscription.organization.name')->label(__('superadmin.organizations.singular'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable(),
                TextColumn::make('period')
                    ->searchable(),
                TextColumn::make('old_expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('new_expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
