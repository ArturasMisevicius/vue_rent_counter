<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Details')
                    ->schema([
                        Select::make('organization_id')
                            ->label('Organization')
                            ->options(Organization::query()->select(['id', 'name'])->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('plan')
                            ->label('Plan')
                            ->options(SubscriptionPlan::options())
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(SubscriptionStatus::options())
                            ->required(),
                        DateTimePicker::make('starts_at')->label('Starts At')->required(),
                        DateTimePicker::make('expires_at')->label('Expires At')->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
