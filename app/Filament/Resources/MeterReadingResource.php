<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeterReadingResource\Pages;
use App\Models\MeterReading;
use App\Models\Meter;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static UnitEnum|string|null $navigationGroup = 'Utilities Management';
    
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Reading Information')
                    ->schema([
                        Forms\Components\Select::make('meter_id')
                            ->label('Meter')
                            ->relationship('meter', 'meter_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->meter_number} ({$record->property->building->name} - {$record->property->unit_number})"
                            )
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\DatePicker::make('reading_date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                            
                        Forms\Components\TextInput::make('value')
                            ->label('Reading Value')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->suffix(''),
                            
                        Forms\Components\TextInput::make('zone')
                            ->label('Tariff Zone')
                            ->maxLength(50)
                            ->placeholder('e.g., day, night')
                            ->helperText('Leave empty for single-zone meters'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_estimated')
                            ->label('Estimated Reading')
                            ->helperText('Check if this is an estimated reading'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('meter.meter_number')
                    ->label('Meter')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('meter.property.building.name')
                    ->label('Building')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('meter.property.unit_number')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('reading_date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Reading')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => " {$record->meter->unit}"),
                    
                Tables\Columns\TextColumn::make('zone')
                    ->badge()
                    ->placeholder('Single Zone'),
                    
                Tables\Columns\IconColumn::make('is_estimated')
                    ->label('Est.')
                    ->boolean()
                    ->tooltip('Estimated Reading'),
                    
                Tables\Columns\TextColumn::make('meter.utility_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'electricity' => 'warning',
                        'gas' => 'danger',
                        'water' => 'info',
                        'heat' => 'success',
                        'sewage' => 'gray',
                        default => 'secondary',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meter')
                    ->relationship('meter', 'meter_number')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('utility_type')
                    ->label('Utility Type')
                    ->options([
                        'electricity' => 'Electricity',
                        'gas' => 'Gas',
                        'water' => 'Water',
                        'heat' => 'Heat',
                        'sewage' => 'Sewage',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => 
                                $query->whereHas('meter', fn ($q) => $q->where('utility_type', $value))
                        );
                    }),
                    
                Tables\Filters\Filter::make('reading_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('reading_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('reading_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\TernaryFilter::make('is_estimated'),
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
            ->defaultSort('reading_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeterReadings::route('/'),
            'create' => Pages\CreateMeterReading::route('/create'),
            'edit' => Pages\EditMeterReading::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}