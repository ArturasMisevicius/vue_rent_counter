<?php

namespace App\Filament\Resources\SubscriptionRenewals\Tables;

use App\Models\Organization;
use App\Models\SubscriptionRenewal;
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
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.user'))
                    ->searchable(),
                TextColumn::make('method')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.method'))
                    ->state(fn (SubscriptionRenewal $record): string => $record->methodLabel())
                    ->searchable(),
                TextColumn::make('period')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.period'))
                    ->state(fn (SubscriptionRenewal $record): string => $record->periodLabel())
                    ->searchable(),
                TextColumn::make('old_expires_at')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.old_expires_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('new_expires_at')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.new_expires_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.duration_days'))
                    ->numeric()
                    ->sortable(),
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
