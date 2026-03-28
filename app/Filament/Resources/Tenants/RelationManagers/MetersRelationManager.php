<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Meter;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class MetersRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.meters');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('current_property_meters_count');

        return (string) ($count ?? $ownerRecord->currentPropertyMeters()->count());
    }

    public function getRelationship(): Relation
    {
        $tenant = $this->getOwnerRecord();

        return $tenant->currentPropertyMeters()
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
            ->forOrganization($tenant->organization_id)
            ->withWorkspaceSummary()
            ->ordered();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('identifier')
                    ->label(__('admin.tenants.meters.columns.serial_number'))
                    ->state(fn (Meter $record): string => (string) ($record->identifier ?: $record->name))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record]))
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.tenants.meters.columns.meter_type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('admin.tenants.meters.columns.status'))
                    ->badge(),
                TextColumn::make('latestReading.reading_date')
                    ->label(__('admin.tenants.meters.columns.last_reading_date'))
                    ->state(fn (Meter $record): string => $record->latestReading?->reading_date?->locale(app()->getLocale())->isoFormat('ll') ?? __('admin.tenants.empty.no_readings_yet')),
                TextColumn::make('latestReading.reading_value')
                    ->label(__('admin.tenants.meters.columns.last_value'))
                    ->state(fn (Meter $record): string => $record->latestReading !== null
                        ? self::formatDecimal((float) $record->latestReading->reading_value, 3).' '.($record->unit ?? '')
                        : '—'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('identifier');
    }

    private static function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
