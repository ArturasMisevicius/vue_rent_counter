<?php

namespace App\Filament\Resources\SubscriptionPayments\Tables;

use App\Models\Organization;
use App\Models\SubscriptionPayment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')->label(__('superadmin.organizations.singular'))
                    ->searchable(),
                TextColumn::make('subscription.plan')
                    ->label(__('superadmin.subscriptions_resource.fields.plan'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('duration')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.duration'))
                    ->state(fn (SubscriptionPayment $record): string => $record->durationLabel())
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.currency'))
                    ->searchable(),
                TextColumn::make('paid_at')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.paid_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.reference'))
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
