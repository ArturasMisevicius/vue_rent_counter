<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Organizations\OrganizationResource;
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
        return 'Buildings';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('buildings_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->forSuperadminControlPlane())
            ->columns([
                TextColumn::make('name')
                    ->label('Building Name')
                    ->url(fn (Building $record): string => BuildingResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Address')
                    ->state(fn (Building $record): string => collect([
                        $record->address_line_1,
                        $record->city,
                    ])->filter()->join(', '))
                    ->wrap(),
                TextColumn::make('properties_count')
                    ->label('Number of Properties')
                    ->sortable(),
                TextColumn::make('meters_count')
                    ->label('Number of Meters')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }
}
