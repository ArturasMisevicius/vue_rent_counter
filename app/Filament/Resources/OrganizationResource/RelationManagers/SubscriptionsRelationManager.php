<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use BackedEnum;
use UnitEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = null;

    protected static BackedEnum|string|null $icon = 'heroicon-o-credit-card';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_type')
                    ->label(__('organizations.relations.subscriptions.plan'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                    ->color(fn ($state): string => match ($state) {
                        SubscriptionPlanType::BASIC->value => 'gray',
                        SubscriptionPlanType::PROFESSIONAL->value => 'info',
                        SubscriptionPlanType::ENTERPRISE->value => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionStatus::class))
                    ->color(fn ($state): string => match ($state) {
                        SubscriptionStatus::ACTIVE->value => 'success',
                        SubscriptionStatus::EXPIRED->value => 'danger',
                        SubscriptionStatus::SUSPENDED->value => 'warning',
                        SubscriptionStatus::CANCELLED->value => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('starts_at')
                    ->label(__('organizations.relations.subscriptions.start'))
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('organizations.relations.subscriptions.expiry'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('max_properties')
                    ->label(__('organizations.relations.subscriptions.properties_limit'))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('max_tenants')
                    ->label(__('organizations.relations.subscriptions.tenants_limit'))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SubscriptionStatus::labels()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.subscriptions.view', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('organizations.relations.subscriptions.empty_heading'))
            ->emptyStateDescription(__('organizations.relations.subscriptions.empty_description'))
            ->emptyStateIcon('heroicon-o-credit-card');
    }
}
