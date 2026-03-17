<?php

namespace App\Filament\Resources\Tariffs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TariffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tariffs.view_title'))
                    ->schema([
                        TextEntry::make('provider.name')
                            ->label(__('admin.tariffs.fields.provider')),
                        TextEntry::make('name')
                            ->label(__('admin.tariffs.fields.name')),
                        TextEntry::make('configuration.type')
                            ->label(__('admin.tariffs.fields.pricing_type'))
                            ->formatStateUsing(fn (?string $state): string => __('admin.tariffs.types.'.($state ?? 'flat'))),
                        TextEntry::make('configuration.rate')
                            ->label(__('admin.tariffs.fields.rate')),
                        TextEntry::make('configuration.currency')
                            ->label(__('admin.tariffs.fields.currency')),
                        TextEntry::make('active_from')
                            ->label(__('admin.tariffs.fields.active_from'))
                            ->dateTime(),
                        TextEntry::make('active_until')
                            ->label(__('admin.tariffs.fields.active_until'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
