<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\BuildingResource\Pages;
use App\Filament\Resources\BuildingResource\RelationManagers;
use App\Models\Building;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class BuildingResource extends Resource
{
    use HasTranslatedValidation;

    protected static ?string $model = Building::class;

    protected static string $translationPrefix = 'buildings.validation';

    protected static ?string $navigationLabel = 'Buildings';

    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Operations';
    }

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('buildings.labels.name'))
                    ->required()
                    ->maxLength(255)
                    ->validationAttribute('name')
                    ->validationMessages(self::getValidationMessages('name')),

                Forms\Components\TextInput::make('address')
                    ->label('Address')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->validationAttribute('address')
                    ->validationMessages(self::getValidationMessages('address')),

                Forms\Components\TextInput::make('total_apartments')
                    ->label('Total Apartments')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->integer()
                    ->validationAttribute('total_apartments')
                    ->validationMessages(self::getValidationMessages('total_apartments')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('buildings.labels.name'))
                    ->searchable()
                    ->sortable(),

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
                // Table row actions removed - use page header actions instead
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
