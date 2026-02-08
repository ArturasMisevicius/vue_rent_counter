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
use App\Models\AuditLog;
use App\Services\Audit\ConfigurationRollbackService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
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
                Tables\Actions\Action::make('audit_history')
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

                Tables\Actions\Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (ServiceConfiguration $record): bool => 
                        AuditLog::where('auditable_type', ServiceConfiguration::class)
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
