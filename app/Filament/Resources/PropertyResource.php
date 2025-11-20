<?php

namespace App\Filament\Resources;

use App\Enums\PropertyType;
use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Properties';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    // Integrate PropertyPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Property::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Property::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Hide from tenant users (Requirements 9.1, 9.2, 9.3)
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role !== \App\Enums\UserRole::TENANT;
    }

    public static function form(Form $form): Form
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
                
                Forms\Components\Select::make('building_id')
                    ->label('Building')
                    ->options(Building::all()->pluck('address', 'id'))
                    ->searchable()
                    ->nullable()
                    ->validationMessages([
                        'exists' => 'The selected building does not exist.',
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

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
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
                
                Tables\Columns\TextColumn::make('building.address')
                    ->label('Building')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('area_sqm')
                    ->label('Area')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m²')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Property Type')
                    ->options([
                        PropertyType::APARTMENT->value => 'Apartment',
                        PropertyType::HOUSE->value => 'House',
                    ])
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('building_id')
                    ->label('Building')
                    ->relationship('building', 'address')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Properties')
                        ->modalDescription('Are you sure you want to delete these properties? This will also affect related meters and readings.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                ]),
            ])
            ->defaultSort('address', 'asc');
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
