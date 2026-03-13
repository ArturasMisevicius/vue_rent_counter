<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\UserRole;
use App\Filament\Tenant\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Schemas\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Property Resource for Tenant Panel
 * 
 * Allows tenants to view their assigned property information.
 * Read-only access - tenants cannot modify property data.
 */
final class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.my_property');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.my_property');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.property');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.properties');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT && $user->property_id;
    }

    public static function canCreate(): bool
    {
        return false; // Tenants cannot create properties
    }

    public static function canEdit($record): bool
    {
        return false; // Tenants cannot edit properties
    }

    public static function canDelete($record): bool
    {
        return false; // Tenants cannot delete properties
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        return parent::getEloquentQuery()
            ->where('id', $user?->property_id)
            ->with(['building', 'meters.serviceConfiguration.utilityService']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.property_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.labels.name'))
                            ->disabled(),
                        
                        TextInput::make('address')
                            ->label(__('app.labels.address'))
                            ->disabled(),
                        
                        TextInput::make('building.name')
                            ->label(__('app.labels.building'))
                            ->disabled(),
                        
                        TextInput::make('floor')
                            ->label(__('app.labels.floor'))
                            ->disabled(),
                        
                        TextInput::make('apartment_number')
                            ->label(__('app.labels.apartment_number'))
                            ->disabled(),
                        
                        TextInput::make('area')
                            ->label(__('app.labels.area'))
                            ->suffix('m²')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.property_details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('app.labels.name')),
                        
                        TextEntry::make('address')
                            ->label(__('app.labels.address')),
                        
                        TextEntry::make('building.name')
                            ->label(__('app.labels.building')),
                        
                        TextEntry::make('floor')
                            ->label(__('app.labels.floor')),
                        
                        TextEntry::make('apartment_number')
                            ->label(__('app.labels.apartment_number')),
                        
                        TextEntry::make('area')
                            ->label(__('app.labels.area'))
                            ->suffix(' m²'),
                    ]),
                
                Section::make(__('app.sections.utility_services'))
                    ->schema([
                        TextEntry::make('meters_count')
                            ->label(__('app.labels.total_meters'))
                            ->getStateUsing(fn ($record) => $record->meters->count()),
                        
                        TextEntry::make('active_services')
                            ->label(__('app.labels.active_services'))
                            ->getStateUsing(function ($record) {
                                return $record->meters
                                    ->pluck('serviceConfiguration.utilityService.name')
                                    ->unique()
                                    ->implode(', ');
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.labels.name'))
                    ->searchable(),
                
                TextColumn::make('address')
                    ->label(__('app.labels.address'))
                    ->searchable(),
                
                TextColumn::make('building.name')
                    ->label(__('app.labels.building')),
                
                TextColumn::make('floor')
                    ->label(__('app.labels.floor')),
                
                TextColumn::make('apartment_number')
                    ->label(__('app.labels.apartment_number')),
                
                TextColumn::make('area')
                    ->label(__('app.labels.area'))
                    ->suffix(' m²'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'view' => Pages\ViewProperty::route('/{record}'),
        ];
    }
}