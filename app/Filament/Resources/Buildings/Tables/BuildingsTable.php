<?php

namespace App\Filament\Resources\Buildings\Tables;

use App\Filament\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;

class BuildingsTable
{
    public static function configure(Table $table): Table
    {
        self::overrideFilterResetLabel();

        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.buildings.columns.building_name'))
                    ->url(fn (Building $record): string => BuildingResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label(__('admin.buildings.columns.address'))
                    ->state(fn (Building $record): string => $record->address)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $searchQuery) use ($search): void {
                            $searchQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('address_line_1', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('postal_code', 'like', "%{$search}%");
                        });
                    })
                    ->wrap(),
                TextColumn::make('properties_count')
                    ->label(__('admin.buildings.columns.properties'))
                    ->summarize([
                        Sum::make('totalProperties')->label(__('admin.filters.total')),
                    ])
                    ->sortable(),
                TextColumn::make('meters_count')
                    ->label(__('admin.buildings.columns.meters'))
                    ->summarize([
                        Sum::make('totalMeters')->label(__('admin.filters.total')),
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.buildings.columns.date_created'))
                    ->date('F j, Y')
                    ->sortable(),
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
                    ->query(function (Builder $query, array $data): Builder {
                        $organizationId = $data['value'] ?? null;

                        if (blank($organizationId)) {
                            return $query;
                        }

                        return $query->forOrganization((int) $organizationId);
                    }),
                Filter::make('created_between')
                    ->label(__('admin.filters.date_range'))
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('admin.filters.from')),
                        DatePicker::make('created_to')
                            ->label(__('admin.filters.to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['created_from'] ?? null),
                                fn (Builder $fromQuery): Builder => $fromQuery->whereDate('created_at', '>=', $data['created_from']),
                            )
                            ->when(
                                filled($data['created_to'] ?? null),
                                fn (Builder $toQuery): Builder => $toQuery->whereDate('created_at', '<=', $data['created_to']),
                            );
                    }),
            ])
            ->emptyStateHeading(__('admin.buildings.empty_state.heading'))
            ->emptyStateDescription(__('admin.buildings.empty_state.description'))
            ->emptyStateActions([
                Action::make('createBuilding')
                    ->label(__('admin.buildings.actions.new_building'))
                    ->url(BuildingResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit')),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->using(fn (Building $record) => app(DeleteBuildingAction::class)->handle($record))
                    ->authorize(function (Building $record): bool {
                        $user = Auth::user();

                        if (! $user instanceof User) {
                            return false;
                        }

                        return Gate::forUser($user)->check('delete', $record);
                    })
                    ->disabled(fn (Building $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                    ->tooltip(fn (Building $record): ?string => $record->adminDeletionBlockedReason()),
            ])
            ->searchPlaceholder(__('admin.buildings.search_placeholder'))
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('name');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => 'Clear Filters',
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => 'Išvalyti filtrus',
        ], 'lt', 'filament-tables');
    }
}
