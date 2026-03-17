<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\Superadmin\Subscriptions\StoreSubscriptionRequest;
use App\Http\Requests\Superadmin\Subscriptions\UpdateSubscriptionRequest;
use App\Models\Subscription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription')
                    ->schema([
                        Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name', fn (Builder $query): Builder => $query
                                ->select([
                                    'id',
                                    'name',
                                ]))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules(fn (): array => StoreSubscriptionRequest::ruleset()['organization_id']),
                        Select::make('plan')
                            ->label('Plan')
                            ->options(SubscriptionPlan::options())
                            ->required()
                            ->rules(fn (): array => StoreSubscriptionRequest::ruleset()['plan']),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                SubscriptionStatus::TRIALING->value => 'Trialing',
                                SubscriptionStatus::ACTIVE->value => 'Active',
                                SubscriptionStatus::EXPIRED->value => 'Expired',
                                SubscriptionStatus::SUSPENDED->value => 'Suspended',
                                SubscriptionStatus::CANCELLED->value => 'Cancelled',
                            ])
                            ->required()
                            ->rules(fn (): array => StoreSubscriptionRequest::ruleset()['status']),
                        DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->required()
                            ->rules(fn (string $operation, ?Subscription $record): array => $operation === 'create'
                                ? StoreSubscriptionRequest::ruleset()['starts_at']
                                : UpdateSubscriptionRequest::ruleset()['starts_at']),
                        DateTimePicker::make('expires_at')
                            ->label('Expires at')
                            ->required()
                            ->rules(fn (string $operation, ?Subscription $record): array => $operation === 'create'
                                ? StoreSubscriptionRequest::ruleset()['expires_at']
                                : UpdateSubscriptionRequest::ruleset()['expires_at']),
                        Toggle::make('is_trial')
                            ->label('Trial')
                            ->required()
                            ->rules(fn (string $operation, ?Subscription $record): array => $operation === 'create'
                                ? StoreSubscriptionRequest::ruleset()['is_trial']
                                : UpdateSubscriptionRequest::ruleset()['is_trial']),
                    ])
                    ->columns(2),
            ]);
    }
}
