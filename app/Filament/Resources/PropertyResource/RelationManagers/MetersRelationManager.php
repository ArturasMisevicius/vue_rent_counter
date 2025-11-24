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
                    ->label(__('meters.relation.meter_type'))
                    ->options(MeterType::class)
                    ->required()
                    ->native(false),
                
                Forms\Components\TextInput::make('serial_number')
                    ->label(__('meters.relation.serial_number'))
                    ->required()
                    ->maxLength(255)
                    ->unique(Meter::class, 'serial_number', ignoreRecord: true),
                
                Forms\Components\DatePicker::make('installation_date')
                    ->label(__('meters.relation.installation_date'))
                    ->required()
                    ->native(false)
                    ->maxDate(now()),
                
                Forms\Components\TextInput::make('initial_reading')
                    ->label(__('meters.relation.initial_reading'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->suffix(__('meters.units.kwh')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('meter_type')
                    ->label(__('meters.relation.type'))
                    ->badge()
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label()),
                
                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('meters.relation.serial_number'))
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('installation_date')
                    ->label(__('meters.relation.installed'))
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('initial_reading')
                    ->label(__('meters.relation.initial_reading'))
                    ->numeric()
                    ->suffix(__('meters.units.kwh')),
                
                Tables\Columns\TextColumn::make('readings_count')
                    ->label(__('meters.relation.readings'))
                    ->counts('readings')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meter_type')
                    ->label(__('meters.relation.meter_type'))
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
            ->emptyStateHeading(__('meters.relation.empty_heading'))
            ->emptyStateDescription(__('meters.relation.empty_description'))
            ->emptyStateActions([
                Actions\CreateAction::make()
                    ->label(__('meters.relation.add_first')),
            ]);
    }
}
