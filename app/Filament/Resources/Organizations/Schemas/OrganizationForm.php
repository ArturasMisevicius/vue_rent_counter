<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
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
                Section::make(__('superadmin.organizations.sections.profile'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('superadmin.organizations.columns.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('superadmin.organizations.columns.slug'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('superadmin.organizations.columns.status'))
                            ->options([
                                OrganizationStatus::ACTIVE->value => __('superadmin.organizations.status.active'),
                                OrganizationStatus::SUSPENDED->value => __('superadmin.organizations.status.suspended'),
                            ])
                            ->default(OrganizationStatus::ACTIVE->value)
                            ->required(),
                        TextInput::make('owner_name')
                            ->label('Owner name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('owner_email')
                            ->label('Owner email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Select::make('plan')
                            ->label('Subscription plan')
                            ->options(collect(SubscriptionPlan::cases())->mapWithKeys(
                                fn (SubscriptionPlan $plan): array => [$plan->value => $plan->label()],
                            )->all())
                            ->default(SubscriptionPlan::BASIC->value)
                            ->required(),
                        Select::make('duration')
                            ->label('Duration')
                            ->options(collect(SubscriptionDuration::cases())->mapWithKeys(
                                fn (SubscriptionDuration $duration): array => [$duration->value => $duration->label()],
                            )->all())
                            ->default(SubscriptionDuration::MONTHLY->value)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
