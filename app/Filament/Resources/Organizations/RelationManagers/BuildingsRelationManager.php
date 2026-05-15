<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Building;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BuildingsRelationManager extends RelationManager
{
    protected static string $relationship = 'buildings';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('superadmin.organizations.relations.buildings.title');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('buildings_count');

        return $count === null ? (string) $ownerRecord->buildings()->count() : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->forSuperadminControlPlane())
            ->columns([
                TextColumn::make('name')
                    ->label(__('superadmin.organizations.relations.buildings.columns.building_name'))
                    ->state(fn (Building $record): string => $record->displayName())
                    ->url(fn (Building $record): string => BuildingResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label(__('superadmin.organizations.relations.buildings.columns.address'))
                    ->state(fn (Building $record): string => collect([
                        $record->address_line_1,
                        $record->city,
                    ])->filter()->join(', '))
                    ->wrap(),
                TextColumn::make('properties_count')
                    ->label(__('superadmin.organizations.relations.buildings.columns.properties_count'))
                    ->sortable(),
                TextColumn::make('meters_count')
                    ->label(__('superadmin.organizations.relations.buildings.columns.meters_count'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.organizations.relations.buildings.columns.date_created'))
                    ->state(fn ($record): string => $record->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }
}
