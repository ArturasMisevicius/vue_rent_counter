<?php

namespace App\Filament\Resources\Meters\Schemas;

use App\Enums\MeterStatus;
use App\Enums\UnitOfMeasurement;
use App\Models\Meter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.meters.sections.details'))
                    ->schema([
                        TextEntry::make('property.name')
                            ->label(__('admin.meters.fields.property'))
                            ->state(fn (Meter $record): string => $record->property?->displayName() ?? '—'),
                        TextEntry::make('property.building.name')
                            ->label(__('admin.meters.fields.building'))
                            ->state(fn (Meter $record): string => $record->property?->building?->displayName() ?? '—'),
                        TextEntry::make('name')
                            ->label(__('admin.meters.fields.name'))
                            ->state(fn (Meter $record): string => $record->displayName()),
                        TextEntry::make('identifier')
                            ->label(__('admin.meters.fields.identifier')),
                        TextEntry::make('type')
                            ->label(__('admin.meters.fields.type'))
                            ->badge(),
                        TextEntry::make('unit')
                            ->label(__('admin.meters.fields.unit'))
                            ->formatStateUsing(fn (?string $state): string => UnitOfMeasurement::tryFrom((string) $state)?->getLabel() ?? ($state ?: '—')),
                        TextEntry::make('status')
                            ->label(__('admin.meters.fields.status'))
                            ->badge()
                            ->color(fn (MeterStatus $state): string => $state->badgeColor()),
                        TextEntry::make('installed_at')
                            ->label(__('admin.meters.fields.installed_at'))
                            ->date(),
                    ])
                    ->columns(2),
            ]);
    }
}
