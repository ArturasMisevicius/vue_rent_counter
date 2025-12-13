<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\UserRole;
use App\Filament\Concerns\HasTenantScoping;
use App\Filament\Resources\ServiceConfigurationResource\Pages;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceConfigurationResource extends Resource
{
    use HasTenantScoping;

    protected static ?string $model = ServiceConfiguration::class;

    protected static ?int $navigationSort = 7;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.property_management');
    }

    public static function getNavigationLabel(): string
    {
        return 'Service Configurations';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role !== UserRole::TENANT;
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role !== UserRole::TENANT;
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->role !== UserRole::TENANT;
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->role !== UserRole::TENANT;
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->role !== UserRole::TENANT;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Configuration')
                ->schema([
                    Forms\Components\Select::make('property_id')
                        ->relationship(
                            name: 'property',
                            titleAttribute: 'address',
                            modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('utility_service_id')
                        ->label('Utility Service')
                        ->relationship(
                            name: 'utilityService',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true)->orderBy('name')
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
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

                    Forms\Components\DateTimePicker::make('effective_from')
                        ->required()
                        ->default(now()),

                    Forms\Components\DateTimePicker::make('effective_until')
                        ->nullable(),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('Rates')
                ->schema([
                    Forms\Components\TextInput::make('rate_schedule.monthly_rate')
                        ->label('Monthly Rate')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€')
                        ->visible(fn (Forms\Get $get): bool => $get('pricing_model') === PricingModel::FIXED_MONTHLY->value),

                    Forms\Components\TextInput::make('rate_schedule.unit_rate')
                        ->label('Unit Rate')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€')
                        ->visible(fn (Forms\Get $get): bool => $get('pricing_model') === PricingModel::CONSUMPTION_BASED->value),

                    Forms\Components\TextInput::make('rate_schedule.fixed_fee')
                        ->label('Fixed Fee')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€')
                        ->visible(fn (Forms\Get $get): bool => $get('pricing_model') === PricingModel::HYBRID->value),

                    Forms\Components\TextInput::make('rate_schedule.unit_rate')
                        ->label('Unit Rate')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€')
                        ->visible(fn (Forms\Get $get): bool => $get('pricing_model') === PricingModel::HYBRID->value),

                    Forms\Components\KeyValue::make('rate_schedule.zone_rates')
                        ->label('Zone Rates')
                        ->keyLabel('Zone')
                        ->valueLabel('Rate (€)')
                        ->addButtonLabel('Add Zone')
                        ->reorderable()
                        ->visible(fn (Forms\Get $get): bool => $get('pricing_model') === PricingModel::TIME_OF_USE->value),

                    Forms\Components\Repeater::make('rate_schedule.tiers')
                        ->label('Tiers')
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
                        ->visible(fn (Forms\Get $get): bool => $get('pricing_model') === PricingModel::TIERED_RATES->value),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.address')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('utilityService.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pricing_model')
                    ->badge()
                    ->formatStateUsing(fn (?PricingModel $state): ?string => $state?->label()),

                Tables\Columns\TextColumn::make('effective_from')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('Property')
                    ->relationship('property', 'address')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('utility_service_id')
                    ->label('Service')
                    ->relationship('utilityService', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('pricing_model')
                    ->options(PricingModel::labels())
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceConfigurations::route('/'),
            'create' => Pages\CreateServiceConfiguration::route('/create'),
            'edit' => Pages\EditServiceConfiguration::route('/{record}/edit'),
        ];
    }
}
