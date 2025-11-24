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

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.tariffs');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.configuration');
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
                Forms\Components\Section::make(__('tariffs.sections.basic_information'))
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->label(__('tariffs.forms.provider'))
                            ->options(Provider::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->validationMessages([
                                'required' => __('tariffs.validation.provider_id.required'),
                                'exists' => __('tariffs.validation.provider_id.exists'),
                            ]),
                        
                        Forms\Components\TextInput::make('name')
                            ->label(__('tariffs.forms.name'))
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => __('tariffs.validation.name.required'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('tariffs.sections.effective_period'))
                    ->schema([
                        Forms\Components\DatePicker::make('active_from')
                            ->label(__('tariffs.forms.active_from'))
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('tariffs.validation.active_from.required'),
                            ]),
                        
                        Forms\Components\DatePicker::make('active_until')
                            ->label(__('tariffs.forms.active_until'))
                            ->nullable()
                            ->native(false)
                            ->after('active_from')
                            ->validationMessages([
                                'after' => __('tariffs.validation.active_until.after'),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('tariffs.sections.configuration'))
                    ->schema([
                        Forms\Components\Select::make('configuration.type')
                            ->label(__('tariffs.forms.type'))
                            ->options(TariffType::labels())
                            ->required()
                            ->native(false)
                            ->live()
                            ->validationMessages([
                                'required' => __('tariffs.validation.configuration.type.required'),
                                'in' => __('tariffs.validation.configuration.type.in'),
                            ]),
                        
                        Forms\Components\Select::make('configuration.currency')
                            ->label(__('tariffs.forms.currency'))
                            ->options([
                                'EUR' => 'EUR (€)',
                            ])
                            ->default('EUR')
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('tariffs.validation.configuration.currency.required'),
                                'in' => __('tariffs.validation.configuration.currency.in'),
                            ]),
                        
                        // Flat rate field - only shown when type is 'flat'
                        Forms\Components\TextInput::make('configuration.rate')
                            ->label(__('tariffs.forms.flat_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->suffix(__('app.units.euro'))
                            ->visible(fn (Get $get): bool => $get('configuration.type') === 'flat')
                            ->required(fn (Get $get): bool => $get('configuration.type') === 'flat')
                            ->validationMessages([
                                'required' => __('tariffs.validation.configuration.rate.required_if'),
                                'numeric' => __('tariffs.validation.configuration.rate.numeric'),
                                'min' => __('tariffs.validation.configuration.rate.min'),
                            ]),
                        
                        // Time-of-use zones - only shown when type is 'time_of_use'
                        Forms\Components\Repeater::make('configuration.zones')
                            ->label(__('tariffs.forms.zones'))
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->label(__('tariffs.forms.zone_id'))
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder(__('tariffs.forms.zone_placeholder'))
                                    ->validationMessages([
                                        'required' => __('tariffs.validation.configuration.zones.id.required_with'),
                                    ]),
                                
                                Forms\Components\TextInput::make('start')
                                    ->label(__('tariffs.forms.start_time'))
                                    ->required()
                                    ->placeholder(__('tariffs.forms.start_placeholder'))
                                    ->regex('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/')
                                    ->validationMessages([
                                        'required' => __('tariffs.validation.configuration.zones.start.required_with'),
                                        'regex' => __('tariffs.validation.configuration.zones.start.regex'),
                                    ]),
                                
                                Forms\Components\TextInput::make('end')
                                    ->label(__('tariffs.forms.end_time'))
                                    ->required()
                                    ->placeholder(__('tariffs.forms.end_placeholder'))
                                    ->regex('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/')
                                    ->validationMessages([
                                        'required' => __('tariffs.validation.configuration.zones.end.required_with'),
                                        'regex' => __('tariffs.validation.configuration.zones.end.regex'),
                                    ]),
                                
                                Forms\Components\TextInput::make('rate')
                                    ->label(__('tariffs.forms.zone_rate'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.0001)
                                    ->suffix(__('app.units.euro'))
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('tariffs.validation.configuration.zones.rate.required_with'),
                                        'numeric' => __('tariffs.validation.configuration.zones.rate.numeric'),
                                        'min' => __('tariffs.validation.configuration.zones.rate.min'),
                                    ]),
                            ])
                            ->columns(4)
                            ->visible(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
                            ->required(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
                            ->minItems(1)
                            ->defaultItems(0)
                            ->addActionLabel(__('tariffs.forms.add_zone'))
                            ->validationMessages([
                                'required' => __('tariffs.validation.configuration.zones.required_if'),
                                'min' => __('tariffs.validation.configuration.zones.min'),
                            ]),
                        
                        // Optional fields
                        Forms\Components\Select::make('configuration.weekend_logic')
                            ->label(__('tariffs.forms.weekend_logic'))
                            ->options(WeekendLogic::labels())
                            ->nullable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
                            ->helperText(__('tariffs.forms.weekend_helper')),
                        
                        Forms\Components\TextInput::make('configuration.fixed_fee')
                            ->label(__('tariffs.forms.fixed_fee'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('app.units.euro'))
                            ->nullable()
                            ->helperText(__('tariffs.forms.fixed_fee_helper')),
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
                    ->label(__('tariffs.labels.provider'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('provider.service_type')
                    ->label(__('tariffs.labels.service_type'))
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
                    ->label(__('tariffs.forms.name'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('configuration.type')
                    ->label(__('tariffs.forms.type'))
                    ->badge()
                    ->color(fn (string $state): string => match (TariffType::tryFrom($state)) {
                        TariffType::FLAT => 'success',
                        TariffType::TIME_OF_USE => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => TariffType::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_from')
                    ->label(__('tariffs.forms.active_from'))
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('active_until')
                    ->label(__('tariffs.forms.active_until'))
                    ->date()
                    ->sortable()
                    ->placeholder(__('tariffs.forms.no_end_date')),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('tariffs.labels.status'))
                    ->boolean()
                    ->getStateUsing(function (Tariff $record): bool {
                        return $record->isActiveOn(now());
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('tariffs.labels.created_at'))
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
