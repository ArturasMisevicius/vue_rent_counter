<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\Columns;

use App\Enums\PropertyType;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

/**
 * Defines table columns for PropertyResource.
 *
 * Centralizes column configuration for better maintainability
 * and testability.
 */
class PropertyTableColumns
{
    /**
     * Get all table columns for the property resource.
     *
     * @return array<Tables\Columns\Column>
     */
    public static function get(): array
    {
        return [
            self::addressColumn(),
            self::typeColumn(),
            self::buildingColumn(),
            self::tenantColumn(),
            self::areaColumn(),
            self::metersCountColumn(),
            self::createdAtColumn(),
        ];
    }

    /**
     * Address column with search and copy functionality.
     */
    private static function addressColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('address')
            ->label(__('properties.labels.address'))
            ->searchable()
            ->sortable()
            ->copyable()
            ->copyMessage(__('properties.tooltips.copy_address'))
            ->weight('medium');
    }

    /**
     * Property type column with badge styling.
     */
    private static function typeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('type')
            ->label(__('properties.labels.type'))
            ->badge()
            ->color(fn (PropertyType $state): string => match ($state) {
                PropertyType::APARTMENT => 'info',
                PropertyType::HOUSE => 'success',
                PropertyType::COMMERCIAL => 'warning',
            })
            ->formatStateUsing(fn (?PropertyType $state): ?string => $state?->label())
            ->sortable();
    }

    /**
     * Building relationship column.
     */
    private static function buildingColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('building.address')
            ->label(__('properties.labels.building'))
            ->searchable()
            ->sortable()
            ->toggleable()
            ->placeholder(__('app.common.dash'));
    }

    /**
     * Tenant relationship column with occupancy status.
     */
    private static function tenantColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('tenants.name')
            ->label(__('properties.labels.current_tenant'))
            ->searchable()
            ->sortable()
            ->toggleable()
            ->badge()
            ->color('success')
            ->placeholder(__('properties.badges.vacant'))
            ->tooltip(fn (?Model $record): ?string => $record?->tenants?->first()?->name
                    ? __('properties.tooltips.occupied_by', ['name' => $record->tenants->first()->name])
                    : __('properties.tooltips.no_tenant')
            );
    }

    /**
     * Area column with numeric formatting.
     */
    private static function areaColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('area_sqm')
            ->label(__('properties.labels.area'))
            ->numeric(decimalPlaces: 2)
            ->suffix(__('app.units.square_meter_spaced'))
            ->sortable()
            ->alignEnd();
    }

    /**
     * Meters count column with badge.
     */
    private static function metersCountColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('meters_count')
            ->label(__('properties.labels.installed_meters'))
            ->counts('meters')
            ->badge()
            ->color('gray')
            ->tooltip(__('properties.tooltips.meters_count'))
            ->toggleable();
    }

    /**
     * Created at timestamp column.
     */
    private static function createdAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label(__('properties.labels.created'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
