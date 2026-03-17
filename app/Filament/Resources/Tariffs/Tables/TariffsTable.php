<?php

namespace App\Filament\Resources\Tariffs\Tables;

use App\Enums\TariffType;
use App\Filament\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Provider;
use App\Models\Tariff;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TariffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.tariffs.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider.name')
                    ->label(__('admin.tariffs.columns.provider'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider.service_type')
                    ->label(__('admin.tariffs.columns.service_type'))
                    ->badge(),
                TextColumn::make('configuration_summary')
                    ->label(__('admin.tariffs.columns.configuration'))
                    ->state(fn (Tariff $record): string => self::formatConfiguration($record->configuration)),
                TextColumn::make('active_from')
                    ->label(__('admin.tariffs.columns.active_from'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('active_until')
                    ->label(__('admin.tariffs.columns.active_until'))
                    ->state(
                        fn (Tariff $record): string => $record->active_until?->format('Y-m-d H:i') ?? __('admin.tariffs.empty.active_until'),
                    )
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider_id')
                    ->label(__('admin.tariffs.fields.provider'))
                    ->options(fn (): array => Provider::query()
                        ->forOrganization(app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('configuration_type')
                    ->label(__('admin.tariffs.fields.type'))
                    ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($query, $type) => $query->where('configuration->type', $type)))
                    ->options(TariffType::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (Tariff $record) => app(DeleteTariffAction::class)->handle($record))
                    ->authorize(fn (Tariff $record): bool => TariffResource::canDelete($record)),
            ])
            ->defaultSort('active_from', 'desc');
    }

    /**
     * @param  array<string, mixed>|null  $configuration
     */
    private static function formatConfiguration(?array $configuration): string
    {
        if ($configuration === null || $configuration === []) {
            return __('admin.tariffs.empty.configuration');
        }

        $parts = [];

        if (isset($configuration['type'])) {
            $parts[] = TariffType::tryFrom((string) $configuration['type'])?->label() ?? (string) $configuration['type'];
        }

        if (filled($configuration['currency'] ?? null)) {
            $parts[] = (string) $configuration['currency'];
        }

        if (filled($configuration['rate'] ?? null)) {
            $parts[] = number_format((float) $configuration['rate'], 4);
        }

        return $parts !== [] ? implode(' · ', $parts) : __('admin.tariffs.empty.configuration');
    }
}
