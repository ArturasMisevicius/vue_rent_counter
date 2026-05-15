<?php

namespace App\Filament\Resources\ServiceConfigurations\Tables;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ServiceConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('property.name')
                    ->label(__('admin.service_configurations.columns.property'))
                    ->state(fn (ServiceConfiguration $record): string => $record->property?->displayName() ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('utilityService.name')
                    ->label(__('admin.service_configurations.columns.utility_service'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider.name')
                    ->label(__('admin.service_configurations.columns.provider'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tariff.name')
                    ->label(__('admin.service_configurations.fields.tariff'))
                    ->toggleable(),
                TextColumn::make('pricing_model')
                    ->label(__('admin.service_configurations.columns.pricing_model'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => $record->pricing_model?->getLabel() ?? '—'),
                TextColumn::make('distribution_method')
                    ->label(__('admin.service_configurations.fields.distribution_method'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => $record->distribution_method?->getLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('effective_from')
                    ->label(__('admin.service_configurations.fields.effective_from'))
                    ->state(fn (ServiceConfiguration $record): string => $record->effective_from?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('effective_until')
                    ->label(__('admin.service_configurations.fields.effective_until'))
                    ->state(fn (ServiceConfiguration $record): string => $record->effective_until?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.service_configurations.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                SelectFilter::make('property')
                    ->label(__('admin.service_configurations.fields.property'))
                    ->options(fn (): array => Property::query()
                        ->select(['id', 'organization_id', 'building_id', 'name'])
                        ->when(
                            ! (static::currentUser()?->isSuperadmin() ?? false),
                            fn (Builder $query): Builder => $query->where('organization_id', static::currentUser()?->organization_id),
                        )
                        ->orderBy('name')
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (Property $property): array => [$property->id => $property->displayName()])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forPropertyValue($data['value'] ?? null)),
                TernaryFilter::make('is_active')
                    ->label(__('admin.service_configurations.fields.is_active'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->forActiveValue(true),
                        false: fn (Builder $query): Builder => $query->forActiveValue(false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
