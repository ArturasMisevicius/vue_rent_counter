<?php

namespace App\Filament\Resources\SubscriptionRenewals\Schemas;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionRenewalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subscription_id')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.subscription'))
                    ->relationship('subscription', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('method')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.method'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.subscription_renewals.methods', [
                        'automatic',
                        'manual',
                    ]))
                    ->required()
                    ->default('manual'),
                Select::make('period')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.period'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.subscription_renewals.periods', [
                        'monthly',
                        'quarterly',
                        'annually',
                    ]))
                    ->required()
                    ->default('annually'),
                DateTimePicker::make('old_expires_at')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.old_expires_at'))
                    ->required(),
                DateTimePicker::make('new_expires_at')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.new_expires_at'))
                    ->required(),
                TextInput::make('duration_days')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.duration_days'))
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
