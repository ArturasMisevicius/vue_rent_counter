<?php

namespace App\Filament\Resources\UtilityServices\Tables;

use App\Enums\ServiceType;
use App\Enums\UnitOfMeasurement;
use App\Models\Organization;
use App\Models\User;
use App\Models\UtilityService;
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

class UtilityServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->placeholder(__('admin.utility_services.placeholders.global_template'))
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.utility_services.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_of_measurement')
                    ->label(__('admin.utility_services.columns.unit_of_measurement'))
                    ->formatStateUsing(fn (?string $state): string => UnitOfMeasurement::tryFrom((string) $state)?->getLabel() ?? ($state ?: '—'))
                    ->sortable(),
                TextColumn::make('service_type_bridge')
                    ->label(__('admin.utility_services.fields.service_type_bridge'))
                    ->badge()
                    ->state(fn (UtilityService $record): string => $record->service_type_bridge?->getLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('default_pricing_model')
                    ->label(__('admin.utility_services.columns.default_pricing_model'))
                    ->badge()
                    ->state(fn (UtilityService $record): string => $record->default_pricing_model?->getLabel() ?? '—'),
                TextColumn::make('service_configurations_count')
                    ->label(__('admin.providers.fields.service_configurations_count'))
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_global_template')
                    ->label(__('admin.utility_services.columns.template'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('admin.utility_services.fields.is_active'))
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
                SelectFilter::make('service_type_bridge')
                    ->label(__('admin.utility_services.fields.service_type_bridge'))
                    ->options(ServiceType::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forServiceTypeValue($data['value'] ?? null)),
                TernaryFilter::make('is_global_template')
                    ->label(__('admin.utility_services.columns.template'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->forGlobalTemplateValue(true),
                        false: fn (Builder $query): Builder => $query->forGlobalTemplateValue(false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_active')
                    ->label(__('admin.utility_services.fields.is_active'))
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
