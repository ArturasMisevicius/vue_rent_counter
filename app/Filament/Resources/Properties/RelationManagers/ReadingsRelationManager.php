<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\MeterReading;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReadingsRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.readings');
    }

    public function getRelationship(): Relation|Builder
    {
        $property = $this->getOwnerRecord();

        return MeterReading::query()
            ->forOrganization($property->organization_id)
            ->forProperty($property->getKey())
            ->withWorkspaceRelations()
            ->latestFirst();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.name')
                    ->label(__('admin.meter_readings.columns.meter'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.meter_readings.columns.reading_value'))
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 3, '.', ''), '0'), '.'))
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label(__('admin.meter_readings.columns.reading_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->badge(),
                TextColumn::make('submission_method')
                    ->label(__('admin.meter_readings.columns.submission_method'))
                    ->badge(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (MeterReading $record): string => MeterReadingResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('reading_date', 'desc');
    }
}
