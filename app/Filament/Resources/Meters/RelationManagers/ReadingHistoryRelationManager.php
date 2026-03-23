<?php

namespace App\Filament\Resources\Meters\RelationManagers;

use App\Filament\Resources\Meters\MeterResource;
use App\Models\Meter;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReadingHistoryRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return MeterResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.meters.sections.history');
    }

    public function getRelationship(): Relation
    {
        /** @var Meter $meter */
        $meter = $this->getOwnerRecord();

        return $meter->readings()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'validation_status',
                'submission_method',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->with([
                'submittedBy:id,name',
            ])
            ->latestFirst();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.meters.sections.history'))
            ->columns([
                TextColumn::make('reading_date')
                    ->label(__('admin.meter_readings.columns.reading_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.meter_readings.columns.reading_value'))
                    ->formatStateUsing(fn (mixed $state): string => rtrim(rtrim(number_format((float) $state, 3, '.', ''), '0'), '.'))
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->badge(),
                TextColumn::make('submission_method')
                    ->label(__('admin.meter_readings.columns.submission_method'))
                    ->badge(),
                TextColumn::make('submittedBy.name')
                    ->label(__('admin.meter_readings.fields.submitted_by'))
                    ->default(__('admin.meter_readings.empty.submitted_by')),
                TextColumn::make('notes')
                    ->label(__('admin.meter_readings.fields.notes'))
                    ->default(__('admin.meter_readings.empty.notes'))
                    ->wrap(),
            ])
            ->defaultSort('reading_date', 'desc');
    }
}
