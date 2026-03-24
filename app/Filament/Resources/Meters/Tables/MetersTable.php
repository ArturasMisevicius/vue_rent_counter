<?php

namespace App\Filament\Resources\Meters\Tables;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Filament\Actions\Admin\Meters\DeleteMeterAction;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MetersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.meters.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identifier')
                    ->label(__('admin.meters.columns.identifier'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label(__('admin.meters.columns.property'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.building.name')
                    ->label(__('admin.meters.columns.building'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.meters.columns.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('admin.meters.columns.status'))
                    ->badge()
                    ->color(fn (MeterStatus $state): string => $state->badgeColor()),
                TextColumn::make('unit')
                    ->label(__('admin.meters.columns.unit'))
                    ->toggleable(),
                TextColumn::make('latestReading.reading_value')
                    ->label(__('admin.meters.columns.latest_reading'))
                    ->default(__('admin.meters.empty.readings'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('admin.meters.fields.building'))
                    ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, function ($query, $buildingId): void {
                        $query->whereHas('property', fn ($query) => $query->where('building_id', $buildingId));
                    }))
                    ->options(function (): array {
                        $query = Building::query()->select(['id', 'name', 'organization_id']);

                        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                        $user = Auth::user();

                        if ($organizationId !== null) {
                            $query->where('organization_id', $organizationId);
                        } elseif (! ($user instanceof User && $user->isSuperadmin())) {
                            $query->whereKey(-1);
                        }

                        return $query
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),
                SelectFilter::make('property_id')
                    ->label(__('admin.meters.fields.property'))
                    ->options(function (): array {
                        $query = Property::query()->select(['id', 'name', 'organization_id']);

                        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                        $user = Auth::user();

                        if ($organizationId !== null) {
                            $query->where('organization_id', $organizationId);
                        } elseif (! ($user instanceof User && $user->isSuperadmin())) {
                            $query->whereKey(-1);
                        }

                        return $query
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),
                SelectFilter::make('type')
                    ->label(__('admin.meters.fields.type'))
                    ->options(MeterType::options()),
                SelectFilter::make('status')
                    ->label(__('admin.meters.fields.status'))
                    ->options(MeterStatus::options()),
            ])
            ->emptyStateHeading(__('admin.meters.empty_state.heading'))
            ->emptyStateDescription(__('admin.meters.empty_state.description'))
            ->emptyStateActions([
                Action::make('createMeter')
                    ->label(__('admin.meters.empty_state.action'))
                    ->url(MeterResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (Meter $record) => app(DeleteMeterAction::class)->handle($record))
                    ->authorize(function (Meter $record): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && $user->can('view', $record);
                    }),
            ])
            ->defaultSort('name');
    }
}
