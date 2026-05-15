<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\MeterReading;
use App\Models\MeterReading as MeterReadingModel;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReadingsRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.readings');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('meter_readings_count');

        return (string) ($count ?? $ownerRecord->meterReadings()->count());
    }

    public function getRelationship(): Relation
    {
        $property = $this->getOwnerRecord();

        return $property->meterReadings()
            ->select([
                'meter_readings.id',
                'meter_readings.organization_id',
                'meter_readings.property_id',
                'meter_readings.meter_id',
                'meter_readings.submitted_by_user_id',
                'meter_readings.reading_value',
                'meter_readings.reading_date',
                'meter_readings.validation_status',
                'meter_readings.submission_method',
                'meter_readings.notes',
                'meter_readings.created_at',
                'meter_readings.updated_at',
            ])
            ->selectSub(
                MeterReadingModel::query()
                    ->from('meter_readings as previous_meter_readings')
                    ->select('previous_meter_readings.reading_value')
                    ->whereColumn('previous_meter_readings.meter_id', 'meter_readings.meter_id')
                    ->where(function (Builder $query): void {
                        $query
                            ->whereColumn('previous_meter_readings.reading_date', '<', 'meter_readings.reading_date')
                            ->orWhere(function (Builder $sameDayQuery): void {
                                $sameDayQuery
                                    ->whereColumn('previous_meter_readings.reading_date', 'meter_readings.reading_date')
                                    ->whereColumn('previous_meter_readings.id', '<', 'meter_readings.id');
                            });
                    })
                    ->orderByDesc('previous_meter_readings.reading_date')
                    ->orderByDesc('previous_meter_readings.id')
                    ->limit(1),
                'previous_reading_value',
            )
            ->forOrganization($property->organization_id)
            ->withWorkspaceRelations()
            ->latestFirst();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.identifier')
                    ->label(__('admin.meter_readings.columns.meter_serial'))
                    ->state(fn (MeterReading $record): string => (string) ($record->meter?->identifier ?: $record->meter?->displayName() ?: '—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label(__('admin.meter_readings.columns.reading_date'))
                    ->state(fn (MeterReading $record): string => $record->reading_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.meter_readings.columns.value'))
                    ->state(fn (MeterReading $record): string => self::formatDecimal((float) $record->reading_value, 3).' '.($record->meter?->unit ?? ''))
                    ->sortable(),
                TextColumn::make('consumption_since_previous')
                    ->label(__('admin.meter_readings.columns.consumption_since_previous'))
                    ->state(function (MeterReading $record): string {
                        $previousValue = $record->getAttribute('previous_reading_value');

                        if ($previousValue === null) {
                            return '—';
                        }

                        $consumption = (float) $record->reading_value - (float) $previousValue;

                        return self::formatDecimal($consumption, 3).' '.($record->meter?->unit ?? '');
                    }),
                TextColumn::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->badge(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (MeterReading $record): string => MeterReadingResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('reading_date', 'desc');
    }

    private static function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
