<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Meter;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class MetersRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.meters');
    }

    public function getRelationship(): Relation
    {
        $tenant = $this->getOwnerRecord();

        return $tenant->currentPropertyMeters()
            ->forOrganization($tenant->organization_id)
            ->withWorkspaceSummary()
            ->ordered();
    }

    public function table(Table $table): Table
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
