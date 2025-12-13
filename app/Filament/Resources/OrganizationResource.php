<?php

namespace App\Filament\Resources;


use App\Enums\SubscriptionPlanType;
use App\Filament\Resources\OrganizationResource\Pages;
use App\Filament\Resources\OrganizationResource\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Resources\OrganizationResource\RelationManagers\PropertiesRelationManager;
use App\Filament\Resources\OrganizationResource\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\OrganizationResource\RelationManagers\UsersRelationManager;
use App\Models\Organization;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = null;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 5;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('organizations.navigation');
    }

    /**
     * Hide from non-superadmin users (Requirements 9.1, 9.2, 9.3).
     * Organizations are superadmin-only system management resources.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof \App\Models\User && $user->role === \App\Enums\UserRole::SUPERADMIN;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('organizations.sections.details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('organizations.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label(__('organizations.labels.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('organizations.helper_text.slug')),
                        
                        Forms\Components\TextInput::make('email')
                            ->label(__('organizations.labels.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label(__('organizations.labels.phone'))
                            ->tel()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('domain')
                            ->label(__('organizations.labels.domain'))
                            ->maxLength(255)
                            ->helperText(__('organizations.helper_text.domain')),
                    ])->columns(2),

                Forms\Components\Section::make(__('organizations.sections.subscription'))
                    ->schema([
                        Forms\Components\Select::make('plan')
                            ->label(__('organizations.labels.plan'))
                            ->options(SubscriptionPlanType::labels())
                            ->required()
                            ->default(SubscriptionPlanType::BASIC->value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $plan = $state instanceof BackedEnum ? $state->value : $state;
                                $limits = [
                                    SubscriptionPlanType::BASIC->value => ['properties' => 100, 'users' => 10],
                                    SubscriptionPlanType::PROFESSIONAL->value => ['properties' => 500, 'users' => 50],
                                    SubscriptionPlanType::ENTERPRISE->value => ['properties' => 9999, 'users' => 999],
                                ];
                                $set('max_properties', $limits[$plan]['properties']);
                                $set('max_users', $limits[$plan]['users']);
                            }),
                        
                        Forms\Components\TextInput::make('max_properties')
                            ->label(__('organizations.labels.max_properties'))
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('max_users')
                            ->label(__('organizations.labels.max_users'))
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1),
                        
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label(__('organizations.labels.trial_end'))
                            ->helperText(__('organizations.helper_text.trial')),
                        
                        Forms\Components\DateTimePicker::make('subscription_ends_at')
                            ->label(__('organizations.labels.subscription_end'))
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make(__('organizations.sections.regional'))
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->label(__('organizations.labels.timezone'))
                            ->options([
                                'Europe/Vilnius' => 'Europe/Vilnius',
                                'Europe/London' => 'Europe/London',
                                'America/New_York' => 'America/New_York',
                                'UTC' => 'UTC',
                            ])
                            ->required()
                            ->default('Europe/Vilnius'),
                        
                        Forms\Components\Select::make('locale')
                            ->label(__('organizations.labels.locale'))
                            ->options([
                                'lt' => 'Lithuanian',
                                'en' => 'English',
                                'ru' => 'Russian',
                            ])
                            ->required()
                            ->default('lt'),
                        
                        Forms\Components\Select::make('currency')
                            ->label(__('organizations.labels.currency'))
                            ->options([
                                'EUR' => 'EUR (€)',
                                'USD' => 'USD ($)',
                            ])
                            ->required()
                            ->default('EUR'),
                    ])->columns(3),

                Forms\Components\Section::make(__('organizations.sections.status'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('organizations.labels.is_active'))
                            ->default(true)
                            ->helperText(__('organizations.helper_text.inactive')),
                        
                        Forms\Components\DateTimePicker::make('suspended_at')
                            ->label(__('organizations.labels.suspended_at'))
                            ->disabled()
                            ->helperText(__('organizations.helper_text.suspended_at')),
                        
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label(__('organizations.labels.suspension_reason'))
                            ->maxLength(500)
                            ->rows(3)
                            ->disabled()
                            ->helperText(__('organizations.helper_text.suspension_reason')),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('organizations.labels.name'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label(__('organizations.labels.email'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('plan')
                    ->label(__('organizations.labels.plan'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                    ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                        SubscriptionPlanType::BASIC->value => 'gray',
                        SubscriptionPlanType::PROFESSIONAL->value => 'info',
                        SubscriptionPlanType::ENTERPRISE->value => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('organizations.labels.is_active')),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('organizations.labels.users'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('properties_count')
                    ->counts('properties')
                    ->label(__('organizations.labels.properties'))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subscription_ends_at')
                    ->label(__('organizations.labels.subscription_end'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->subscription_ends_at?->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('organizations.labels.created_at') ?? __('properties.labels.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options(SubscriptionPlanType::labels()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('organizations.labels.is_active'))
                    ->placeholder(__('organizations.filters.active_placeholder'))
                    ->trueLabel(__('organizations.filters.active_only'))
                    ->falseLabel(__('organizations.filters.inactive_only')),
                
                Tables\Filters\Filter::make('subscription_expired')
                    ->query(fn (Builder $query): Builder => $query->where('subscription_ends_at', '<', now()))
                    ->label(__('organizations.labels.expired_subscriptions')),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('subscription_ends_at', '>=', now())
                        ->where('subscription_ends_at', '<=', now()->addDays(14)))
                    ->label(__('organizations.labels.expiring_soon')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('organizations.modals.delete_heading'))
                    ->modalDescription(__('organizations.modals.delete_description'))
                    ->before(function (Tables\Actions\DeleteAction $action, Organization $record) {
                        // Check if organization has any relations
                        $hasUsers = $record->users()->exists();
                        $hasProperties = $record->properties()->exists();
                        $hasBuildings = $record->buildings()->exists();
                        $hasInvoices = $record->invoices()->exists();
                        $hasMeters = $record->meters()->exists();
                        $hasTenants = $record->tenants()->exists();
                        
                        if ($hasUsers || $hasProperties || $hasBuildings || $hasInvoices || $hasMeters || $hasTenants) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('organizations.notifications.cannot_delete'))
                                ->body(__('organizations.notifications.has_relations', [
                                    'users' => $record->users()->count(),
                                    'properties' => $record->properties()->count(),
                                    'buildings' => $record->buildings()->count(),
                                    'invoices' => $record->invoices()->count(),
                                    'meters' => $record->meters()->count(),
                                    'tenants' => $record->tenants()->count(),
                                ]))
                                ->danger()
                                ->persistent()
                                ->send();
                            
                            $action->cancel();
                        }
                    })
                    ->after(function (Organization $record) {
                        // Delete all relations in proper order
                        \DB::transaction(function () use ($record) {
                            // Delete activity logs
                            $record->activityLogs()->delete();
                            
                            // Delete invitations
                            $record->invitations()->delete();
                            
                            // Delete super admin audit logs
                            $record->superAdminAuditLogs()->delete();
                        });
                    })
                    ->successNotificationTitle(__('organizations.notifications.deleted')),
                
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label(__('organizations.labels.suspension_reason'))
                            ->maxLength(500),
                    ])
                    ->action(function (Organization $record, array $data) {
                        $record->suspend($data['reason']);
                    })
                    ->visible(fn (Organization $record) => !$record->isSuspended())
                    ->successNotificationTitle(__('organizations.actions.suspend')),
                
                Tables\Actions\Action::make('reactivate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Organization $record) => $record->reactivate())
                    ->visible(fn (Organization $record) => $record->isSuspended())
                    ->successNotificationTitle(__('organizations.actions.reactivate')),
                
                Tables\Actions\Action::make('impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('organizations.modals.impersonate_heading'))
                    ->modalDescription(__('organizations.modals.impersonate_description'))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label(__('organizations.modals.impersonation_reason'))
                            ->helperText(__('organizations.helper_text.impersonation_reason'))
                            ->maxLength(500),
                    ])
                    ->action(function (Organization $record, array $data) {
                        // Get the organization's admin user
                        $adminUser = $record->users()->where('role', 'admin')->first();
                        
                        if (!$adminUser) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('organizations.modals.no_admin'))
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Store impersonation data in session
                        session([
                            'impersonating' => true,
                            'impersonator_id' => auth()->id(),
                            'impersonated_user_id' => $adminUser->id,
                            'impersonation_reason' => $data['reason'],
                            'impersonation_started_at' => now(),
                        ]);
                        
                        // Log the impersonation
                        \App\Models\OrganizationActivityLog::create([
                            'organization_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'impersonation_started',
                            'resource_type' => \App\Models\User::class,
                            'resource_id' => $adminUser->id,
                            'after_data' => [
                                'reason' => $data['reason'],
                                'target_user' => $adminUser->email,
                            ],
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);
                        
                        // Switch to the admin user
                        auth()->login($adminUser);
                        
                            \Filament\Notifications\Notification::make()
                            ->title(__('organizations.modals.impersonation_started'))
                            ->success()
                            ->send();
                        
                        // Redirect to their dashboard
                        return redirect()->route('filament.admin.pages.dashboard');
                    })
                    ->visible(fn (Organization $record) => $record->is_active),
                
                Tables\Actions\Action::make('view_analytics')
                    ->label(__('organizations.labels.analytics'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->url(fn (Organization $record): string => '#')
                    ->openUrlInNewTab(false)
                    ->tooltip(__('organizations.labels.analytics')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label(__('organizations.actions.suspend_selected'))
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->required()
                                ->label(__('organizations.labels.suspension_reason'))
                                ->helperText(__('organizations.helper_text.suspension_reason'))
                                ->maxLength(500),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $suspended = 0;
                            $failed = 0;
                            
                            foreach ($records as $record) {
                                try {
                                    if (!$record->isSuspended()) {
                                        $record->suspend($data['reason']);
                                        $suspended++;
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(
                                    __('organizations.notifications.bulk_suspended', ['count' => $suspended])
                                    . ($failed > 0 ? __('organizations.notifications.bulk_failed_suffix', ['count' => $failed]) : '')
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_reactivate')
                        ->label(__('organizations.actions.reactivate_selected'))
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $reactivated = 0;
                            $failed = 0;
                            
                            foreach ($records as $record) {
                                try {
                                    if ($record->isSuspended()) {
                                        $record->reactivate();
                                        $reactivated++;
                                    }
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(
                                    __('organizations.notifications.bulk_reactivated', ['count' => $reactivated])
                                    . ($failed > 0 ? __('organizations.notifications.bulk_failed_suffix', ['count' => $failed]) : '')
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_change_plan')
                        ->label(__('organizations.actions.change_plan'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('new_plan')
                                ->label(__('organizations.labels.new_plan'))
                                ->options(SubscriptionPlanType::labels())
                                ->required()
                                ->live()
                                ->helperText(__('organizations.helper_text.change_plan')),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $updated = 0;
                            $failed = 0;
                            
                            $limits = [
                                SubscriptionPlanType::BASIC->value => ['properties' => 100, 'users' => 10],
                                SubscriptionPlanType::PROFESSIONAL->value => ['properties' => 500, 'users' => 50],
                                SubscriptionPlanType::ENTERPRISE->value => ['properties' => 9999, 'users' => 999],
                            ];
                            
                            foreach ($records as $record) {
                                try {
                                    $record->update([
                                        'plan' => $data['new_plan'],
                                        'max_properties' => $limits[$data['new_plan']]['properties'],
                                        'max_users' => $limits[$data['new_plan']]['users'],
                                    ]);
                                    $updated++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(
                                    __('organizations.notifications.bulk_updated', ['count' => $updated])
                                    . ($failed > 0 ? __('organizations.notifications.bulk_failed_suffix', ['count' => $failed]) : '')
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\ExportBulkAction::make()
                        ->label(__('organizations.actions.export_selected'))
                        ->icon('heroicon-o-arrow-down-tray'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
            PropertiesRelationManager::class,
            SubscriptionsRelationManager::class,
            ActivityLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'view' => Pages\ViewOrganization::route('/{record}'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->withoutGlobalScopes();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'slug', 'domain', 'phone'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Email' => $record->email,
            'Plan' => ucfirst($record->plan?->value ?? 'Unknown'),
            'Subscription Ends' => $record->subscription_ends_at?->format('Y-m-d') ?? 'N/A',
        ];
    }

    public static function getGlobalSearchResultActions(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            \Filament\GlobalSearch\Actions\Action::make('edit')
                ->iconButton()
                ->icon('heroicon-m-pencil-square')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }
}
