<?php

namespace App\Filament\Resources\Buildings\RelationManagers;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Meter;
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
        return BuildingResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.buildings.tabs.meters');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->select([
                    'meters.id',
                    'meters.organization_id',
                    'meters.property_id',
                    'meters.name',
                    'meters.identifier',
                    'meters.type',
                    'meters.status',
                    'meters.unit',
                    'meters.installed_at',
                    'meters.created_at',
                    'meters.updated_at',
                ])
                ->withWorkspaceSummary()
                ->ordered())
            ->columns([
                TextColumn::make('identifier')
                    ->label(__('admin.meters.columns.serial_number'))
                    ->state(fn (Meter $record): string => (string) ($record->identifier ?: $record->name))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.meters.columns.meter_type'))
                    ->badge(),
                TextColumn::make('property.name')
                    ->label(__('admin.meters.columns.property'))
                    ->url(fn (Meter $record): ?string => $record->property !== null
                        ? PropertyResource::getUrl('view', ['record' => $record->property])
                        : null),
                TextColumn::make('latestReading.reading_date')
                    ->label(__('admin.meters.columns.last_reading_date'))
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_date?->locale(app()->getLocale())->isoFormat('ll') ?? __('admin.meters.empty.no_readings_yet')),
                TextColumn::make('latestReading.reading_value')
                    ->label(__('admin.meters.columns.last_value'))
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_value !== null
                        ? self::formatDecimal((float) $record->latestReading->reading_value, 3).' '.($record->unit ?? '')
                        : '—'),
                TextColumn::make('status')
                    ->label(__('admin.meters.columns.status'))
                    ->badge(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record])),
            ]);
    }

    private static function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
