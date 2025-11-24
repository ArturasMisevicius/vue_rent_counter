<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ServiceType;
use App\Enums\TariffType;
use App\Enums\WeekendLogic;
use App\Filament\Resources\TariffResource\Pages;
use App\Filament\Resources\TariffResource\RelationManagers;
use App\Models\Provider;
use App\Models\Tariff;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use UnitEnum;

/**
 * Filament resource for managing tariffs.
 *
 * Provides CRUD operations for tariffs with:
 * - Tenant-scoped data access
 * - Role-based navigation visibility (admin only)
 * - Support for flat and time-of-use tariff types
 * - Zone configuration for time-of-use tariffs
 * - Relationship management (providers)
 *
 * @see \App\Models\Tariff
 * @see \App\Policies\TariffPolicy
 */
class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static ?string $navigationLabel = 'Tariffs';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Configuration';
    }

    // Integrate TariffPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Tariff::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Tariff::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Hide from non-admin users (Requirements 9.1, 9.2, 9.3)
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->label('Provider')
                            ->options(Provider::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->validationMessages([
                                'required' => 'Provider is required',
                                'exists' => 'Selected provider does not exist',
                            ]),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Tariff Name')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Tariff name is required',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Effective Period')
                    ->schema([
                        Forms\Components\DatePicker::make('active_from')
                            ->label('Active From')
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Active from date is required',
                            ]),
                        
                        Forms\Components\DatePicker::make('active_until')
                            ->label('Active Until')
                            ->nullable()
                            ->native(false)
                            ->after('active_from')
                            ->validationMessages([
                                'after' => 'Active until date must be after active from date',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tariff Configuration')
                    ->schema([
                        Forms\Components\Select::make('configuration.type')
                            ->label('Tariff Type')
                            ->options(TariffType::labels())
                            ->required()
                            ->native(false)
                            ->live()
                            ->validationMessages([
                                'required' => 'Tariff type is required',
                                'in' => 'Tariff type must be either flat or time_of_use',
                            ]),
                        
                        Forms\Components\Select::make('configuration.currency')
                            ->label('Currency')
                            ->options([
                                'EUR' => 'EUR (€)',
                            ])
                            ->default('EUR')
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Currency is required',
                                'in' => 'Currency must be EUR',
                            ]),
                        
                        // Flat rate field - only shown when type is 'flat'
                        Forms\Components\TextInput::make('configuration.rate')
                            ->label('Rate (€/kWh or €/m³)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->suffix('€')
                            ->visible(fn (Get $get): bool => $get('configuration.type') === 'flat')
                            ->required(fn (Get $get): bool => $get('configuration.type') === 'flat')
                            ->validationMessages([
                                'required' => 'Rate is required for flat tariffs',
                                'numeric' => 'Rate must be a number',
                                'min' => 'Rate must be a positive number',
                            ]),
                        
                        // Time-of-use zones - only shown when type is 'time_of_use'
                        Forms\Components\Repeater::make('configuration.zones')
                            ->label('Time-of-Use Zones')
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->label('Zone ID')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('e.g., day, night, peak')
                                    ->validationMessages([
                                        'required' => 'Zone ID is required',
                                    ]),
                                
                                Forms\Components\TextInput::make('start')
                                    ->label('Start Time')
                                    ->required()
                                    ->placeholder('HH:MM (e.g., 07:00)')
                                    ->regex('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/')
                                    ->validationMessages([
                                        'required' => 'Start time is required',
                                        'regex' => 'Zone start time must be in HH:MM format (24-hour)',
                                    ]),
                                
                                Forms\Components\TextInput::make('end')
                                    ->label('End Time')
                                    ->required()
                                    ->placeholder('HH:MM (e.g., 23:00)')
                                    ->regex('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/')
                                    ->validationMessages([
                                        'required' => 'End time is required',
                                        'regex' => 'Zone end time must be in HH:MM format (24-hour)',
                                    ]),
                                
                                Forms\Components\TextInput::make('rate')
                                    ->label('Rate (€/kWh)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.0001)
                                    ->suffix('€')
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Rate is required for each zone',
                                        'numeric' => 'Rate must be a number',
                                        'min' => 'Zone rate must be a positive number',
                                    ]),
                            ])
                            ->columns(4)
                            ->visible(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
                            ->required(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
                            ->minItems(1)
                            ->defaultItems(0)
                            ->addActionLabel('Add Zone')
                            ->validationMessages([
                                'required' => 'Zones are required for time-of-use tariffs',
                                'min' => 'At least one zone is required for time-of-use tariffs',
                            ]),
                        
                        // Optional fields
                        Forms\Components\Select::make('configuration.weekend_logic')
                            ->label('Weekend Logic')
                            ->options(WeekendLogic::labels())
                            ->nullable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
                            ->helperText('How to handle weekends for time-of-use tariffs'),
                        
                        Forms\Components\TextInput::make('configuration.fixed_fee')
                            ->label('Fixed Monthly Fee')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('€')
                            ->nullable()
                            ->helperText('Optional fixed monthly fee (e.g., meter rental)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('provider.name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('provider.service_type')
                    ->label('Service Type')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof ServiceType ? $state : ServiceType::tryFrom((string) $state)) {
                        ServiceType::ELECTRICITY => 'warning',
                        ServiceType::WATER => 'info',
                        ServiceType::HEATING => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ($state instanceof ServiceType ? $state : ServiceType::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Tariff Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('configuration.type')
                    ->label('Tariff Type')
                    ->badge()
                    ->color(fn (string $state): string => match (TariffType::tryFrom($state)) {
                        TariffType::FLAT => 'success',
                        TariffType::TIME_OF_USE => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => TariffType::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_from')
                    ->label('Active From')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_until')
                    ->label('Active Until')
                    ->date()
                    ->sortable()
                    ->placeholder('No end date'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(function (Tariff $record): bool {
                        return $record->isActiveOn(now());
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
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
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Bulk actions removed for Filament v4 compatibility
            ])
            ->defaultSort('active_from', 'desc');
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
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
}
