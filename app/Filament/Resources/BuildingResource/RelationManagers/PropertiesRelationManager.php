<?php

namespace App\Filament\Resources\BuildingResource\RelationManagers;

use App\Enums\PropertyType;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('address')
                    ->label('Address')
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => 'The property address is required.',
                    ]),
                
                Forms\Components\Select::make('type')
                    ->label('Property Type')
                    ->options([
                        PropertyType::APARTMENT->value => 'Apartment',
                        PropertyType::HOUSE->value => 'House',
                    ])
                    ->required()
                    ->native(false)
                    ->validationMessages([
                        'required' => 'The property type is required.',
                    ]),
                
                Forms\Components\TextInput::make('area_sqm')
                    ->label('Area')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->suffix('m²')
                    ->step(0.01)
                    ->validationMessages([
                        'required' => 'The property area is required.',
                        'numeric' => 'The property area must be a number.',
                        'min' => 'The property area must be at least 0 square meters.',
                        'max' => 'The property area cannot exceed 10,000 square meters.',
                    ]),
                
                Forms\Components\Select::make('tenants')
                    ->label('Tenant')
                    ->relationship('tenants', 'name')
                    ->searchable()
                    ->nullable()
                    ->helperText('Optional: Assign a tenant to this property'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Property Type')
                    ->badge()
                    ->color(fn (PropertyType $state): string => match ($state) {
                        PropertyType::APARTMENT => 'info',
                        PropertyType::HOUSE => 'success',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('area_sqm')
                    ->label('Area')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m²')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Automatically set tenant_id and building_id
                        $data['tenant_id'] = auth()->user()->tenant_id;
                        $data['building_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
