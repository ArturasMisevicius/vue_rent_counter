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
                Section::make(__('admin.tariffs.sections.details'))
                    ->schema([
                        TextEntry::make('provider.name')
                            ->label(__('admin.tariffs.fields.provider')),
                        TextEntry::make('provider.service_type')
                            ->label(__('admin.tariffs.fields.service_type'))
                            ->badge()
                            ->formatStateUsing(fn ($state): string => __('admin.providers.service_types.'.($state->value ?? $state))),
                        TextEntry::make('name')
                            ->label(__('admin.tariffs.fields.name')),
                        TextEntry::make('remote_id')
                            ->label(__('admin.tariffs.fields.remote_id'))
                            ->default(__('admin.tariffs.empty.remote_id')),
                        TextEntry::make('configuration_summary')
                            ->label(__('admin.tariffs.fields.configuration'))
                            ->state(fn ($record): string => self::formatConfiguration($record->configuration)),
                        TextEntry::make('active_from')
                            ->label(__('admin.tariffs.fields.active_from'))
                            ->dateTime(),
                        TextEntry::make('active_until')
                            ->label(__('admin.tariffs.fields.active_until'))
                            ->dateTime()
                            ->placeholder(__('admin.tariffs.empty.active_until')),
                        TextEntry::make('service_configurations_count')
                            ->label(__('admin.tariffs.fields.service_configurations_count')),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $configuration
     */
    private static function formatConfiguration(?array $configuration): string
    {
        if ($configuration === null || $configuration === []) {
            return __('admin.tariffs.empty.configuration');
        }

        $parts = [];

        if (isset($configuration['type'])) {
            $parts[] = __('admin.tariffs.types.'.(string) $configuration['type']);
        }

        if (filled($configuration['currency'] ?? null)) {
            $parts[] = (string) $configuration['currency'];
        }

        if (filled($configuration['rate'] ?? null)) {
            $parts[] = number_format((float) $configuration['rate'], 4);
        }

        return $parts !== [] ? implode(' · ', $parts) : __('admin.tariffs.empty.configuration');
    }
}
