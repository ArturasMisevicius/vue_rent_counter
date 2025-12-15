<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                                if (!$state) {
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
                            ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::TIME_OF_USE->value)
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
