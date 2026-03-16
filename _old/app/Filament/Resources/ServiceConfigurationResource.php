<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\UserRole;
use App\Filament\Concerns\HasTenantScoping;
use App\Filament\Resources\ServiceConfigurationResource\Pages;
use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Models\UtilityService;
use App\Services\Audit\ConfigurationRollbackService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

                    Forms\Components\TextInput::make('rate_schedule.rate')
                        ->label('Rate')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€')
                        ->helperText('Legacy flat rate. Prefer modern pricing models for new services.')
                        ->required(fn (Get $get): bool => $get('pricing_model') === PricingModel::FLAT->value)
                        ->visible(fn (Get $get): bool => $get('pricing_model') === PricingModel::FLAT->value),

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
                \Filament\Actions\Action::make('audit_history')
                    ->label('Audit History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Configuration Change History')
                    ->modalDescription('View all configuration changes and rollback options for this service configuration.')
                    ->modalContent(fn (ServiceConfiguration $record) => view('filament.tenant.modals.change-details', [
                        'modelType' => ServiceConfiguration::class,
                        'modelId' => $record->id,
                        'modelName' => "{$record->property->address} - {$record->utilityService->name}",
                    ]))
                    ->modalWidth('7xl')
                    ->slideOver(),

                \Filament\Actions\Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (ServiceConfiguration $record): bool => AuditLog::where('auditable_type', ServiceConfiguration::class)
                        ->where('auditable_id', $record->id)
                        ->where('event', '!=', 'rollback')
                        ->exists()
                    )
                    ->form([
                        Forms\Components\Select::make('audit_log_id')
                            ->label('Select Change to Rollback')
                            ->options(function (ServiceConfiguration $record): array {
                                return AuditLog::where('auditable_type', ServiceConfiguration::class)
                                    ->where('auditable_id', $record->id)
                                    ->where('event', '!=', 'rollback')
                                    ->orderBy('created_at', 'desc')
                                    ->get()
                                    ->mapWithKeys(function (AuditLog $audit) {
                                        $user = $audit->user_id ? "User {$audit->user_id}" : 'System';
                                        $date = $audit->created_at->format('M j, Y H:i');

                                        return [$audit->id => "{$audit->event} by {$user} on {$date}"];
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\Textarea::make('reason')
                            ->label('Rollback Reason')
                            ->placeholder(__('dashboard.audit.placeholders.rollback_reason'))
                            ->required()
                            ->maxLength(500),
                        Forms\Components\Toggle::make('notify_stakeholders')
                            ->label('Notify Stakeholders')
                            ->default(true)
                            ->helperText('Send notifications to administrators and managers about this rollback.'),
                    ])
                    ->action(function (ServiceConfiguration $record, array $data): void {
                        $rollbackService = app(ConfigurationRollbackService::class);

                        $result = $rollbackService->performRollback(
                            auditLogId: (int) $data['audit_log_id'],
                            userId: auth()->id(),
                            reason: $data['reason'],
                            notifyStakeholders: $data['notify_stakeholders'] ?? true,
                        );

                        if ($result['success']) {
                            Notification::make()
                                ->title(__('dashboard.audit.notifications.rollback_success'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('dashboard.audit.notifications.rollback_failed'))
                                ->body(implode(', ', $result['errors'] ?? []))
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.audit.rollback_confirmation'))
                    ->modalDescription(__('dashboard.audit.rollback_warning')),

                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
