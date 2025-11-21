<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BuildingResource\Pages;
use App\Filament\Resources\BuildingResource\RelationManagers;
use App\Models\Building;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BuildingResource extends Resource
{
    protected static ?string $model = Building::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Buildings';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 3;

    // Integrate BuildingPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Building::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Building::class);
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
                    ->columnSpanFull()
                    ->validationAttribute('address')
                    ->validationMessages([
                        'required' => 'The building address is required.',
                        'max' => 'The building address may not be greater than 255 characters.',
                    ]),
                
                Forms\Components\TextInput::make('total_apartments')
                    ->label('Total Apartments')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->integer()
                    ->validationAttribute('total_apartments')
                    ->validationMessages([
                        'required' => 'The total number of apartments is required.',
                        'numeric' => 'The total number of apartments must be a whole number.',
                        'integer' => 'The total number of apartments must be a whole number.',
                        'min' => 'The building must have at least 1 apartment.',
                        'max' => 'The building cannot have more than 1,000 apartments.',
                    ]),
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
                
                Tables\Columns\TextColumn::make('total_apartments')
                    ->label('Total Apartments')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('properties_count')
                    ->label('Property Count')
                    ->counts('properties')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('address', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PropertiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBuildings::route('/'),
            'create' => Pages\CreateBuilding::route('/create'),
            'edit' => Pages\EditBuilding::route('/{record}/edit'),
        ];
    }
}
