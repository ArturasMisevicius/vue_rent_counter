<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Meter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MetersRelationManager extends RelationManager
{
    protected static string $relationship = 'meters';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.meters');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('meters_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withWorkspaceSummary()->ordered())
            ->columns([
                TextColumn::make('identifier')
                    ->label(__('admin.meters.columns.serial_number'))
                    ->state(fn (Meter $record): string => (string) ($record->identifier ?: $record->name))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.meters.columns.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('admin.meters.columns.status'))
                    ->badge(),
                TextColumn::make('latestReading.reading_date')
                    ->label(__('admin.meters.columns.last_reading_date'))
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_date?->format('F j, Y') ?? __('admin.meters.empty.no_readings_yet')),
                TextColumn::make('latestReading.reading_value')
                    ->label(__('admin.meters.columns.last_value'))
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_value !== null
                        ? rtrim(rtrim(number_format((float) $record->latestReading->reading_value, 3, '.', ''), '0'), '.').' '.($record->unit ?? '')
                        : '—'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('name');
    }
}
