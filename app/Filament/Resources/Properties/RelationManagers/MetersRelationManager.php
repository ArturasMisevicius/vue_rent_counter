<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
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
                    ->state(fn (Meter $record): string => (string) ($record->identifier ?: $record->displayName()))
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
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? __('admin.meters.empty.no_readings_yet')),
                TextColumn::make('latestReading.reading_value')
                    ->label(__('admin.meters.columns.last_value'))
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_value !== null
                        ? self::formatDecimal((float) $record->latestReading->reading_value, 3).' '.($record->unit ?? '')
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

    private static function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
