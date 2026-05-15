<?php

namespace App\Filament\Resources\ServiceConfigurations\Schemas;

use App\Models\ServiceConfiguration;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceConfigurationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.service_configurations.view_title'))
                    ->schema([
                        TextEntry::make('property.name')
                            ->label(__('admin.service_configurations.fields.property'))
                            ->state(fn (ServiceConfiguration $record): string => $record->property?->displayName() ?? '—'),
                        TextEntry::make('utilityService.name')
                            ->label(__('admin.service_configurations.fields.utility_service')),
                        TextEntry::make('provider.name')
                            ->label(__('admin.service_configurations.fields.provider')),
                        TextEntry::make('tariff.name')
                            ->label(__('admin.service_configurations.fields.tariff')),
                        TextEntry::make('pricing_model')
                            ->label(__('admin.service_configurations.fields.pricing_model'))
                            ->badge(),
                        TextEntry::make('distribution_method')
                            ->label(__('admin.service_configurations.fields.distribution_method'))
                            ->badge(),
                        TextEntry::make('rate_schedule.unit_rate')
                            ->label(__('admin.service_configurations.fields.unit_rate')),
                    ])
                    ->columns(2),
            ]);
    }
}
