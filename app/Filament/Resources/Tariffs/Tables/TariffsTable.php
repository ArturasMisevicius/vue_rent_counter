<?php

namespace App\Filament\Resources\Tariffs\Tables;

use App\Enums\TariffType;
use App\Filament\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TariffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider.organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.tariffs.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider.name')
                    ->label(__('admin.tariffs.columns.provider'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tariff_type')
                    ->label(__('admin.tariffs.columns.type'))
                    ->badge(),
                TextColumn::make('rate_display')
                    ->label(__('admin.tariffs.columns.rate'))
                    ->weight('medium'),
                TextColumn::make('active_from')
                    ->label(__('admin.tariffs.columns.active_from'))
                    ->state(fn (Tariff $record): string => $record->active_from?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->sortable(),
                TextColumn::make('active_until')
                    ->label(__('admin.tariffs.columns.active_until'))
                    ->state(
                        fn (Tariff $record): string => $record->active_until?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? __('admin.tariffs.empty.ongoing'),
                    )
                    ->color(fn (Tariff $record): ?string => $record->active_until === null ? 'gray' : null)
                    ->sortable(),
                TextColumn::make('status_display')
                    ->label(__('admin.tariffs.columns.status'))
                    ->badge()
                    ->color(fn (Tariff $record): string => $record->isCurrentlyActive() ? 'success' : 'gray'),
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
                SelectFilter::make('provider_id')
                    ->label(__('admin.tariffs.fields.provider'))
                    ->options(function (): array {
                        $query = Provider::query()->select(['id', 'organization_id', 'name']);

                        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                        $user = Auth::user();

                        if ($organizationId !== null) {
                            $query->where('organization_id', $organizationId);
                        } elseif (! ($user instanceof User && $user->isSuperadmin())) {
                            $query->whereKey(-1);
                        }

                        return $query
                            ->orderBy('name')
                            ->orderBy('id')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->placeholder(__('admin.tariffs.filters.all_providers'))
                    ->query(fn (Builder $query, array $data): Builder => $query->forProviderValue($data['value'] ?? null)),
                SelectFilter::make('configuration_type')
                    ->label(__('admin.tariffs.fields.type'))
                    ->options(TariffType::options())
                    ->placeholder(__('admin.tariffs.filters.all_types'))
                    ->query(fn (Builder $query, array $data): Builder => $query->forConfigurationTypeValue($data['value'] ?? null)),
                SelectFilter::make('status')
                    ->label(__('admin.tariffs.columns.status'))
                    ->options([
                        'active' => __('admin.tariffs.statuses.active'),
                        'inactive' => __('admin.tariffs.statuses.inactive'),
                    ])
                    ->placeholder(__('admin.tariffs.filters.all_statuses'))
                    ->query(fn (Builder $query, array $data): Builder => $query->forStatusValue($data['value'] ?? null)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit')),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->using(fn (Tariff $record) => app(DeleteTariffAction::class)->handle($record))
                    ->authorize(fn (Tariff $record): bool => TariffResource::canDelete($record))
                    ->disabled(fn (Tariff $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                    ->tooltip(fn (Tariff $record): ?string => $record->adminDeletionBlockedReason()),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('active_from', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
