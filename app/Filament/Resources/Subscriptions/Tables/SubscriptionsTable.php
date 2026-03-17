<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Actions\Superadmin\Subscriptions\CancelSubscriptionAction;
use App\Actions\Superadmin\Subscriptions\ExtendSubscriptionAction;
use App\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Support\Superadmin\Usage\OrganizationUsageReader;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('expires_at')
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable(),
                TextColumn::make('plan_name_snapshot')
                    ->label('Plan')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('properties_used')
                    ->label('Properties used')
                    ->state(fn (Subscription $record): int => $record->propertiesUsed(app(OrganizationUsageReader::class))),
                TextColumn::make('tenants_used')
                    ->label('Tenants used')
                    ->state(fn (Subscription $record): int => $record->tenantsUsed()),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->relationship('organization', 'name', fn (Builder $query): Builder => $query
                        ->select([
                            'id',
                            'name',
                        ])),
                SelectFilter::make('plan')
                    ->options(SubscriptionPlan::options()),
                SelectFilter::make('status')
                    ->options([
                        SubscriptionStatus::TRIALING->value => 'Trialing',
                        SubscriptionStatus::ACTIVE->value => 'Active',
                        SubscriptionStatus::EXPIRED->value => 'Expired',
                        SubscriptionStatus::SUSPENDED->value => 'Suspended',
                        SubscriptionStatus::CANCELLED->value => 'Cancelled',
                    ]),
                SelectFilter::make('expiring_within')
                    ->options([
                        '7' => 'Within 7 days',
                        '30' => 'Within 30 days',
                        '90' => 'Within 90 days',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $builder, string $value): Builder => $builder
                            ->expiringWithinDays((int) $value))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('extend')
                    ->label('Extend')
                    ->modalHeading('Extend subscription')
                    ->form([
                        Select::make('duration')
                            ->label('Duration')
                            ->options(collect(SubscriptionDuration::all())
                                ->mapWithKeys(fn (SubscriptionDuration $duration): array => [$duration->value => $duration->label()])
                                ->all())
                            ->required(),
                    ])
                    ->action(fn (Subscription $record, array $data) => app(ExtendSubscriptionAction::class)($record, $data)),
                Action::make('upgradePlan')
                    ->label('Upgrade plan')
                    ->modalHeading('Upgrade subscription plan')
                    ->form([
                        Select::make('plan')
                            ->label('Plan')
                            ->options(SubscriptionPlan::options())
                            ->required(),
                    ])
                    ->action(fn (Subscription $record, array $data) => app(UpgradeSubscriptionPlanAction::class)($record, $data)),
                Action::make('suspend')
                    ->label('Suspend')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend subscription')
                    ->action(fn (Subscription $record) => app(SuspendSubscriptionAction::class)($record)),
                Action::make('cancel')
                    ->label('Cancel')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel subscription')
                    ->action(fn (Subscription $record) => app(CancelSubscriptionAction::class)($record)),
            ]);
    }
}
