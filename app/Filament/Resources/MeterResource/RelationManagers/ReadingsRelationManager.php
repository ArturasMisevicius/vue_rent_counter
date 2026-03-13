<?php

declare(strict_types=1);

namespace App\Filament\Resources\MeterResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Relation manager for meter readings.
 *
 * Displays and manages readings associated with a meter.
 */
class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    protected static ?string $recordTitleAttribute = 'reading_date';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('meters.labels.readings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\DatePicker::make('reading_date')
                    ->label(__('meter_readings.labels.reading_date'))
                    ->required()
                    ->maxDate(now())
                    ->native(false),

                Forms\Components\TextInput::make('reading_value')
                    ->label(__('meter_readings.labels.reading_value'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),

                Forms\Components\TextInput::make('day_reading')
                    ->label(__('meter_readings.labels.day_reading'))
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->visible(fn () => $this->getOwnerRecord()->supports_zones),

                Forms\Components\TextInput::make('night_reading')
                    ->label(__('meter_readings.labels.night_reading'))
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->visible(fn () => $this->getOwnerRecord()->supports_zones),

                Forms\Components\Textarea::make('notes')
                    ->label(__('meter_readings.labels.notes'))
                    ->maxLength(500)
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reading_date')
                    ->label(__('meter_readings.labels.reading_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reading_value')
                    ->label(__('meter_readings.labels.reading_value'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('consumption')
                    ->label(__('meter_readings.labels.consumption'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('day_reading')
                    ->label(__('meter_readings.labels.day_reading'))
                    ->numeric(decimalPlaces: 2)
                    ->toggleable()
                    ->visible(fn () => $this->getOwnerRecord()->supports_zones),

                Tables\Columns\TextColumn::make('night_reading')
                    ->label(__('meter_readings.labels.night_reading'))
                    ->numeric(decimalPlaces: 2)
                    ->toggleable()
                    ->visible(fn () => $this->getOwnerRecord()->supports_zones),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('meter_readings.labels.submitted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('reading_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('meter_readings.filters.from_date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('meter_readings.filters.until_date')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('reading_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('reading_date', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('reading_date', 'desc')
            ->emptyStateHeading(__('meter_readings.empty_state.heading'))
            ->emptyStateDescription(__('meter_readings.empty_state.description'));
    }
}
