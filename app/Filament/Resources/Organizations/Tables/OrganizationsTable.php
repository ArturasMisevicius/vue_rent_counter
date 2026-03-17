<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Organization')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Invitation pending'),
                TextColumn::make('currentSubscription.plan_name_snapshot')
                    ->label('Plan')
                    ->placeholder('No subscription'),
                TextColumn::make('currentSubscription.expires_at')
                    ->label('Renews')
                    ->formatStateUsing(fn ($state): string => $state?->format('M j, Y') ?? 'No renewal date'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        OrganizationStatus::ACTIVE->value => 'Active',
                        OrganizationStatus::SUSPENDED->value => 'Suspended',
                    ]),
                SelectFilter::make('plan')
                    ->options(collect(SubscriptionPlan::cases())
                        ->mapWithKeys(fn (SubscriptionPlan $plan): array => [$plan->value => $plan->label()])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $builder, string $value): Builder => $builder
                            ->whereHas('currentSubscription', fn (Builder $subscriptionQuery): Builder => $subscriptionQuery
                                ->where('plan', $value)))),
                SelectFilter::make('owner')
                    ->relationship('owner', 'name', fn (Builder $query): Builder => $query
                        ->select(['id', 'name'])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
