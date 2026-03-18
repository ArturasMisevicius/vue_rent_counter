<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Properties';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('properties_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withWorkspaceSummary()->ordered())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_number')
                    ->label('Unit')
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label('Building')
                    ->searchable(),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label('Tenant')
                    ->default('Unassigned')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
            ])
            ->defaultSort('name');
    }
}
