<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\UtilityService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceConfigurationsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceConfigurations';

    protected static ?string $title = 'Services';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Service')
                    ->schema([
                        Forms\Components\Select::make('utility_service_id')
                            ->label('Utility Service')
                            ->options(fn (): array => UtilityService::getCachedOptions()->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (! $state) {
                                    return;
                                }

                                $service = UtilityService::find($state);
                                if ($service?->default_pricing_model) {
                                    $set('pricing_model', $service->default_pricing_model->value);
                                }
                            }),

                        Forms\Components\Select::make('pricing_model')
                            ->options(PricingModel::class)
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('distribution_method')
                            ->options(DistributionMethod::class)
                            ->default(DistributionMethod::EQUAL->value)
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_shared_service')
                            ->label('Shared Service')
                            ->default(false)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Effective Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('effective_from')
                            ->required()
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('effective_until')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Rates')
                    ->schema([
                        Forms\Components\TextInput::make('rate_schedule.monthly_rate')
                            ->label('Monthly Rate')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€')
                            ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::FIXED_MONTHLY->value)
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::FIXED_MONTHLY->value),

                        Forms\Components\TextInput::make('rate_schedule.unit_rate')
                            ->label('Unit Rate')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€')
                            ->required(fn (Get $get): bool => in_array($get('pricing_model'), [
                                PricingModel::CONSUMPTION_BASED->value,
                                PricingModel::HYBRID->value,
                            ], true))
                            ->visible(fn (Get $get): bool => in_array($get('pricing_model'), [
                                PricingModel::CONSUMPTION_BASED->value,
                                PricingModel::HYBRID->value,
                            ], true)),

                        Forms\Components\TextInput::make('rate_schedule.fixed_fee')
                            ->label('Fixed Fee')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€')
                            ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::HYBRID->value)
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::HYBRID->value),

                        Forms\Components\KeyValue::make('rate_schedule.zone_rates')
                            ->label('Zone Rates')
                            ->keyLabel('Zone')
                            ->valueLabel('Rate (€)')
                            ->addButtonLabel('Add Zone')
                            ->reorderable()
                            ->required(function (Get $get): bool {
                                if ($get('pricing_model') !== PricingModel::TIME_OF_USE->value) {
                                    return false;
                                }

                                $timeWindows = $get('rate_schedule.time_windows');

                                return ! is_array($timeWindows) || $timeWindows === [];
                            })
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::TIME_OF_USE->value),

                        Forms\Components\Repeater::make('rate_schedule.time_windows')
                            ->label('Time Windows')
                            ->required(function (Get $get): bool {
                                if ($get('pricing_model') !== PricingModel::TIME_OF_USE->value) {
                                    return false;
                                }

                                $zoneRates = $get('rate_schedule.zone_rates');

                                return ! is_array($zoneRates) || $zoneRates === [];
                            })
                            ->minItems(1)
                            ->schema([
                                Forms\Components\TextInput::make('zone')
                                    ->required()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('start')
                                    ->required()
                                    ->placeholder('07:00')
                                    ->rule('regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'),
                                Forms\Components\TextInput::make('end')
                                    ->required()
                                    ->placeholder('23:00')
                                    ->rule('regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'),
                                Forms\Components\TextInput::make('rate')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('€'),
                                Forms\Components\CheckboxList::make('day_types')
                                    ->options([
                                        'weekday' => 'Weekday',
                                        'weekend' => 'Weekend',
                                    ])
                                    ->columns(2),
                                Forms\Components\Select::make('months')
                                    ->multiple()
                                    ->options([
                                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
                                    ]),
                            ])
                            ->columns(3)
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::TIME_OF_USE->value),

                        Forms\Components\Repeater::make('rate_schedule.tiers')
                            ->label('Tiers')
                            ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::TIERED_RATES->value)
                            ->minItems(1)
                            ->schema([
                                Forms\Components\TextInput::make('limit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Upper limit for this tier (leave empty for no limit).'),
                                Forms\Components\TextInput::make('rate')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('€')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::TIERED_RATES->value),

                        Forms\Components\TextInput::make('rate_schedule.rate')
                            ->label('Rate')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€')
                            ->helperText('Legacy flat rate. Prefer modern pricing models for new services.')
                            ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::FLAT->value)
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::FLAT->value),

                        Forms\Components\Textarea::make('rate_schedule.formula')
                            ->label('Formula')
                            ->rows(3)
                            ->helperText('Available variables: consumption, days, month, year, is_summer, is_winter. Add custom variables below.')
                            ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::CUSTOM_FORMULA->value)
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::CUSTOM_FORMULA->value),

                        Forms\Components\KeyValue::make('rate_schedule.variables')
                            ->label('Variables')
                            ->keyLabel('Variable')
                            ->valueLabel('Value')
                            ->addButtonLabel('Add Variable')
                            ->reorderable()
                            ->helperText('Variables must be numeric (boolean values are treated as 1/0).')
                            ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::CUSTOM_FORMULA->value),

                        Forms\Components\TextInput::make('rate_schedule.localization.locale')
                            ->label('Locale Profile')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('rate_schedule.localization.minimum_charge')
                            ->label('Minimum Charge')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('€'),

                        Forms\Components\TextInput::make('rate_schedule.localization.tax_rate')
                            ->label('Tax Rate (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\Select::make('rate_schedule.localization.rounding_mode')
                            ->label('Rounding Mode')
                            ->options([
                                'half_up' => 'Half Up',
                                'half_down' => 'Half Down',
                                'bankers' => 'Bankers',
                                'up' => 'Up',
                                'down' => 'Down',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('rate_schedule.localization.money_precision')
                            ->label('Money Precision')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(6),

                        Forms\Components\Repeater::make('rate_schedule.localization.fixed_charges')
                            ->label('Localized Fixed Charges')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('€'),
                            ])
                            ->columns(2),

                        Forms\Components\Repeater::make('rate_schedule.localization.surcharges')
                            ->label('Localized Surcharges')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('percentage')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                            ])
                            ->columns(2),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('utilityService.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('utilityService.unit_of_measurement')
                    ->label('Unit')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pricing_model')
                    ->label('Pricing')
                    ->badge()
                    ->formatStateUsing(fn (?PricingModel $state): ?string => $state?->label()),

                Tables\Columns\IconColumn::make('is_shared_service')
                    ->label('Shared')
                    ->boolean(),

                Tables\Columns\TextColumn::make('meters_count')
                    ->label('Meters')
                    ->counts('meters')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_from')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = $this->getOwnerRecord()->tenant_id;
                        $data['property_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('effective_from', 'desc');
    }
}
