<?php

declare(strict_types=1);

namespace App\Filament\Resources\TariffResource\Concerns;

use App\Enums\ServiceType;
use App\Enums\TariffType;
use App\Models\Tariff;
use Filament\Tables;

/**
 * Trait for building tariff table columns.
 * 
 * Extracts table column construction logic from TariffResource
 * to improve maintainability and reduce method complexity.
 */
trait BuildsTariffTableColumns
{
    /**
     * Build all table columns for the tariff resource.
     *
     * @return array<Tables\Columns\Column>
     */
    protected static function buildTableColumns(): array
    {
        return [
            static::buildProviderNameColumn(),
            static::buildServiceTypeColumn(),
            static::buildNameColumn(),
            static::buildTariffTypeColumn(),
            static::buildActiveDatesColumns(),
            static::buildIsActiveColumn(),
            static::buildCreatedAtColumn(),
        ];
    }

    /**
     * Build the provider name column.
     *
     * @return Tables\Columns\TextColumn
     */
    protected static function buildProviderNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('provider.name')
            ->label(__('tariffs.labels.provider'))
            ->searchable()
            ->sortable();
    }

    /**
     * Build the service type column with badge formatting.
     *
     * @return Tables\Columns\TextColumn
     */
    protected static function buildServiceTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('provider.service_type')
            ->label(__('tariffs.labels.service_type'))
            ->badge()
            ->color(fn ($state): string => static::getServiceTypeColor($state))
            ->formatStateUsing(fn ($state): string => static::formatServiceType($state))
            ->sortable();
    }

    /**
     * Get the badge color for a service type.
     *
     * @param mixed $state
     * @return string
     */
    protected static function getServiceTypeColor(mixed $state): string
    {
        $serviceType = $state instanceof ServiceType 
            ? $state 
            : ServiceType::tryFrom((string) $state);

        return match ($serviceType) {
            ServiceType::ELECTRICITY => 'warning',
            ServiceType::WATER => 'info',
            ServiceType::HEATING => 'danger',
            default => 'gray',
        };
    }

    /**
     * Format the service type for display.
     *
     * @param mixed $state
     * @return string
     */
    protected static function formatServiceType(mixed $state): string
    {
        $serviceType = $state instanceof ServiceType 
            ? $state 
            : ServiceType::tryFrom((string) $state);

        return $serviceType?->label() ?? (string) $state;
    }

    /**
     * Build the tariff name column.
     *
     * @return Tables\Columns\TextColumn
     */
    protected static function buildNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('name')
            ->label(__('tariffs.forms.name'))
            ->searchable()
            ->sortable();
    }

    /**
     * Build the tariff type column with badge formatting.
     *
     * @return Tables\Columns\TextColumn
     */
    protected static function buildTariffTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('configuration.type')
            ->label(__('tariffs.forms.type'))
            ->badge()
            ->color(fn (string $state): string => static::getTariffTypeColor($state))
            ->formatStateUsing(fn (string $state): string => static::formatTariffType($state))
            ->sortable();
    }

    /**
     * Get the badge color for a tariff type.
     *
     * @param string $state
     * @return string
     */
    protected static function getTariffTypeColor(string $state): string
    {
        return match (TariffType::tryFrom($state)) {
            TariffType::FLAT => 'success',
            TariffType::TIME_OF_USE => 'info',
            default => 'gray',
        };
    }

    /**
     * Format the tariff type for display.
     *
     * @param string $state
     * @return string
     */
    protected static function formatTariffType(string $state): string
    {
        return TariffType::tryFrom($state)?->label() ?? $state;
    }

    /**
     * Build the active date columns.
     *
     * @return array<Tables\Columns\TextColumn>
     */
    protected static function buildActiveDatesColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('active_from')
                ->label(__('tariffs.forms.active_from'))
                ->date()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('active_until')
                ->label(__('tariffs.forms.active_until'))
                ->date()
                ->sortable()
                ->placeholder(__('tariffs.forms.no_end_date')),
        ];
    }

    /**
     * Build the is_active status column.
     *
     * @return Tables\Columns\IconColumn
     */
    protected static function buildIsActiveColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_currently_active')
            ->label(__('tariffs.labels.status'))
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger');
    }

    /**
     * Build the created_at column.
     *
     * @return Tables\Columns\TextColumn
     */
    protected static function buildCreatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label(__('tariffs.labels.created_at'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
