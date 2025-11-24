<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\MeterType;
use App\Models\Meter;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MetersRelationManager extends RelationManager
{
    protected static string $relationship = 'meters';

    protected static ?string $recordTitleAttribute = 'serial_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('meter_type')
                    ->label('Meter Type')
                    ->options(MeterType::class)
                    ->required()
                    ->native(false),
                
                Forms\Components\TextInput::make('serial_number')
                    ->label('Serial Number')
                    ->required()
                    ->maxLength(255)
                    ->unique(Meter::class, 'serial_number', ignoreRecord: true),
                
                Forms\Components\DatePicker::make('installation_date')
                    ->label('Installation Date')
                    ->required()
                    ->native(false)
                    ->maxDate(now()),
                
                Forms\Components\TextInput::make('initial_reading')
                    ->label('Initial Reading')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->suffix('kWh'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('meter_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label()),
                
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('installation_date')
                    ->label('Installed')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('initial_reading')
                    ->label('Initial Reading')
                    ->numeric()
                    ->suffix(' kWh'),
                
                Tables\Columns\TextColumn::make('readings_count')
                    ->label('Readings')
                    ->counts('readings')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meter_type')
                    ->label('Meter Type')
                    ->options(MeterType::labels())
                    ->native(false),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure tenant_id is set from property
                        $data['tenant_id'] = $this->getOwnerRecord()->tenant_id;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No meters installed')
            ->emptyStateDescription('Add meters to track utility consumption for this property.')
            ->emptyStateActions([
                Actions\CreateAction::make()
                    ->label('Add First Meter'),
            ]);
    }
}
