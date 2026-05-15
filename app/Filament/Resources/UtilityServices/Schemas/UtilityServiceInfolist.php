<?php

namespace App\Filament\Resources\UtilityServices\Schemas;

use App\Enums\UnitOfMeasurement;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\UtilityService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UtilityServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.utility_services.view_title'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.utility_services.fields.name'))
                            ->state(fn (UtilityService $record): string => app(DatabaseContentLocalizer::class)->utilityServiceName(
                                $record->name,
                                $record->service_type_bridge,
                            )),
                        TextEntry::make('unit_of_measurement')
                            ->label(__('admin.utility_services.fields.unit_of_measurement'))
                            ->formatStateUsing(fn (?string $state): string => UnitOfMeasurement::tryFrom((string) $state)?->getLabel() ?? ($state ?: '—')),
                        TextEntry::make('default_pricing_model')
                            ->label(__('admin.utility_services.fields.default_pricing_model'))
                            ->badge(),
                        TextEntry::make('service_type_bridge')
                            ->label(__('admin.utility_services.fields.service_type_bridge'))
                            ->badge(),
                        TextEntry::make('description')
                            ->label(__('admin.utility_services.fields.description'))
                            ->state(fn (UtilityService $record): ?string => app(DatabaseContentLocalizer::class)->utilityServiceDescription($record->description)),
                    ])
                    ->columns(2),
            ]);
    }
}
