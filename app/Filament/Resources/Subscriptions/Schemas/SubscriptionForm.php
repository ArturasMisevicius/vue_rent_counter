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
                Section::make(__('superadmin.subscriptions_resource.sections.details'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.subscriptions_resource.fields.organization'))
                            ->options(Organization::query()->select(['id', 'name'])->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('plan')
                            ->label(__('superadmin.subscriptions_resource.fields.plan'))
                            ->options(SubscriptionPlan::options())
                            ->required(),
                        Select::make('status')
                            ->label(__('superadmin.subscriptions_resource.fields.status'))
                            ->options(SubscriptionStatus::options())
                            ->required(),
                        DateTimePicker::make('starts_at')->label(__('superadmin.subscriptions_resource.fields.starts_at'))->required(),
                        DateTimePicker::make('expires_at')->label(__('superadmin.subscriptions_resource.fields.expires_at'))->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
