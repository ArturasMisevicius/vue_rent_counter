<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Tenants\TenantResource;
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
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.readings');
    }

    public function getRelationship(): Relation
    {
        $tenant = $this->getOwnerRecord();

        return $tenant->currentPropertyReadings()
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
            ->forOrganization($tenant->organization_id)
            ->withWorkspaceRelations()
            ->latestFirst();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.identifier')
                    ->label(__('admin.tenants.readings.columns.meter'))
                    ->state(fn (MeterReading $record): string => (string) ($record->meter?->identifier ?: $record->meter?->name ?: '—'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label(__('admin.tenants.readings.columns.reading_date'))
                    ->state(fn (MeterReading $record): string => $record->reading_date?->locale(app()->getLocale())->isoFormat('ll') ?? '—')
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.tenants.readings.columns.value'))
                    ->state(fn (MeterReading $record): string => self::formatDecimal((float) $record->reading_value, 3).' '.($record->meter?->unit ?? ''))
                    ->sortable(),
                TextColumn::make('consumption_since_previous')
                    ->label(__('admin.tenants.readings.columns.consumption'))
                    ->state(function (MeterReading $record): string {
                        $previousValue = $record->getAttribute('previous_reading_value');

                        if ($previousValue === null) {
                            return '—';
                        }

                        $consumption = (float) $record->reading_value - (float) $previousValue;

                        return self::formatDecimal($consumption, 3).' '.($record->meter?->unit ?? '');
                    }),
                TextColumn::make('validation_status')
                    ->label(__('admin.tenants.readings.columns.status'))
                    ->badge(),
                TextColumn::make('submittedBy.name')
                    ->label(__('admin.tenants.readings.columns.submitted_by'))
                    ->default('—'),
                TextColumn::make('submission_method')
                    ->label(__('admin.tenants.readings.columns.submission_method'))
                    ->state(fn (MeterReading $record): string => $record->submission_method === MeterReadingSubmissionMethod::TENANT_PORTAL
                        ? __('admin.tenants.readings.submission_methods.tenant')
                        : __('admin.tenants.readings.submission_methods.admin')),
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
