<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Meter;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MetersRelationManager extends RelationManager
{
    protected static string $relationship = 'meters';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.meters');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('meters_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withWorkspaceSummary()->ordered())
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.meters.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identifier')
                    ->label(__('admin.meters.columns.identifier'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.meters.columns.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('admin.meters.columns.status'))
                    ->badge(),
                TextColumn::make('latestReading.reading_value')
                    ->label(__('admin.meters.columns.latest_reading'))
                    ->default(__('admin.meters.empty.readings')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('name');
    }
}
