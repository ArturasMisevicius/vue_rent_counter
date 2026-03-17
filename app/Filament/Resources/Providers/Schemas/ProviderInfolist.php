<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.providers.view_title'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.providers.fields.name')),
                        TextEntry::make('service_type')
                            ->label(__('admin.providers.fields.service_type'))
                            ->formatStateUsing(fn ($state): string => __('admin.providers.types.'.($state->value ?? $state))),
                        TextEntry::make('contact_info.email')
                            ->label(__('admin.providers.fields.contact_email')),
                        TextEntry::make('contact_info.phone')
                            ->label(__('admin.providers.fields.contact_phone')),
                        TextEntry::make('tariffs_count')
                            ->label(__('admin.providers.fields.tariffs_count')),
                        TextEntry::make('service_configurations_count')
                            ->label(__('admin.providers.fields.service_configurations_count')),
                    ])
                    ->columns(2),
            ]);
    }
}
