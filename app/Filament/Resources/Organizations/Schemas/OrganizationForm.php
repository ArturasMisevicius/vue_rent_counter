<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Http\Requests\Superadmin\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Superadmin\Organizations\UpdateOrganizationRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization')
                    ->schema([
                        TextInput::make('name')
                            ->label('Organization name')
                            ->required()
                            ->maxLength(255)
                            ->rules(StoreOrganizationRequest::ruleset()['name']),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->helperText('Leave blank to generate the slug from the organization name.')
                            ->rules(UpdateOrganizationRequest::ruleset()['slug']),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                OrganizationStatus::ACTIVE->value => 'Active',
                                OrganizationStatus::SUSPENDED->value => 'Suspended',
                            ])
                            ->default(OrganizationStatus::ACTIVE->value)
                            ->required()
                            ->visible(fn (string $operation): bool => $operation === 'edit'),
                    ])
                    ->columns(2),
                Section::make('Owner')
                    ->schema([
                        TextInput::make('owner_name')
                            ->label('Owner name')
                            ->required()
                            ->maxLength(255)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->rules(StoreOrganizationRequest::ruleset()['owner_name']),
                        TextInput::make('owner_email')
                            ->label('Owner email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->rules(StoreOrganizationRequest::ruleset()['owner_email']),
                    ])
                    ->columns(2),
                Section::make('Subscription')
                    ->schema([
                        Select::make('plan')
                            ->label('Plan')
                            ->options(collect(SubscriptionPlan::cases())
                                ->mapWithKeys(fn (SubscriptionPlan $plan): array => [$plan->value => $plan->label()])
                                ->all())
                            ->required()
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->rules(StoreOrganizationRequest::ruleset()['plan']),
                        Select::make('duration')
                            ->label('Billing duration')
                            ->options(collect(SubscriptionDuration::all())
                                ->mapWithKeys(fn (SubscriptionDuration $duration): array => [$duration->value => $duration->label()])
                                ->all())
                            ->required()
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->rules(StoreOrganizationRequest::ruleset()['duration']),
                    ])
                    ->columns(2),
            ]);
    }
}
