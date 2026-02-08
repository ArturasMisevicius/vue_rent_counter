<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Resources\UtilityServiceResource\Pages;
use App\Models\UtilityService;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\Audit\ConfigurationRollbackService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class UtilityServiceResource extends Resource
{
    protected static ?string $model = UtilityService::class;

    protected static ?int $navigationSort = 6;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return 'Utility Services';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Service')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if (!is_string($state) || $state === '') {
                                return;
                            }

                            $set('slug', Str::slug($state));
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Unique identifier used internally.'),

                    Forms\Components\TextInput::make('unit_of_measurement')
                        ->label('Unit')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('kWh, m³, L, m², pcs, etc.'),

                    Forms\Components\Select::make('default_pricing_model')
                        ->label('Default Pricing Model')
                        ->options(PricingModel::class)
                        ->required()
                        ->native(false)
                        ->searchable(),

                    Forms\Components\Textarea::make('description')
                        ->columnSpanFull()
                        ->maxLength(1000),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('Template')
                ->schema([
                    Forms\Components\Toggle::make('is_global_template')
                        ->label('Global Template (Superadmin)')
                        ->inline(false)
                        ->helperText('Global templates are visible to all tenants.')
                        ->visible(fn (): bool => auth()->user()?->role === UserRole::SUPERADMIN),

                    Forms\Components\Select::make('service_type_bridge')
                        ->label('Legacy Bridge')
                        ->options(ServiceType::class)
                        ->native(false)
                        ->nullable()
                        ->visible(fn (): bool => auth()->user()?->role === UserRole::SUPERADMIN),
                ])
                ->columns(2)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_of_measurement')
                    ->label('Unit')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('default_pricing_model')
                    ->label('Pricing')
                    ->badge()
                    ->formatStateUsing(fn (?PricingModel $state): ?string => $state?->label()),

                Tables\Columns\IconColumn::make('is_global_template')
                    ->label('Global')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_global_template')
                    ->label('Global Template'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('clone')
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn (UtilityService $record): bool =>
                        auth()->user()?->role === UserRole::ADMIN && $record->is_global_template
                    )
                    ->action(function (UtilityService $record): void {
                        $tenantId = auth()->user()?->tenant_id;

                        if (!$tenantId) {
                            throw new \RuntimeException('No tenant_id found for current user.');
                        }

                        $record->createTenantCopy($tenantId);
                    }),

                Tables\Actions\Action::make('audit_history')
                    ->label('Audit History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Configuration Change History')
                    ->modalDescription('View all configuration changes and rollback options for this utility service.')
                    ->modalContent(fn (UtilityService $record) => view('filament.tenant.modals.change-details', [
                        'modelType' => UtilityService::class,
                        'modelId' => $record->id,
                        'modelName' => $record->name,
                    ]))
                    ->modalWidth('7xl')
                    ->slideOver(),

                Tables\Actions\Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (UtilityService $record): bool => 
                        AuditLog::where('auditable_type', UtilityService::class)
                            ->where('auditable_id', $record->id)
                            ->where('event', '!=', 'rollback')
                            ->exists()
                    )
                    ->form([
                        Forms\Components\Select::make('audit_log_id')
                            ->label('Select Change to Rollback')
                            ->options(function (UtilityService $record): array {
                                return AuditLog::where('auditable_type', UtilityService::class)
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
                    ->action(function (UtilityService $record, array $data): void {
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

                Tables\Actions\EditAction::make()
                    ->visible(fn (UtilityService $record): bool =>
                        auth()->user()?->role === UserRole::SUPERADMIN || !$record->is_global_template
                    ),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (UtilityService $record): bool =>
                        auth()->user()?->role === UserRole::SUPERADMIN || !$record->is_global_template
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUtilityServices::route('/'),
            'create' => Pages\CreateUtilityService::route('/create'),
            'edit' => Pages\EditUtilityService::route('/{record}/edit'),
        ];
    }
}
