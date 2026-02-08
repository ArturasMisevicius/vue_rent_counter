<?php

namespace App\Filament\Resources;


use BackedEnum;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.subscriptions');
    }

    /**
     * Hide from non-admin users (Requirements 9.1, 9.2, 9.3).
     * Subscriptions are system management resources accessible only to admins and superadmins.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof \App\Models\User && in_array($user->role, [
            \App\Enums\UserRole::SUPERADMIN,
            \App\Enums\UserRole::ADMIN,
        ], true);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isSuperadmin();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isSuperadmin();
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isSuperadmin();
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isSuperadmin();
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isSuperadmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make(__('subscriptions.sections.details'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('subscriptions.labels.organization'))
                            ->relationship('user', 'email')
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->organization_name ?: $record->email)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('subscriptions.helper_text.select_organization')),
                        
                        Forms\Components\Select::make('plan_type')
                            ->label(__('subscriptions.labels.plan_type'))
                            ->options(SubscriptionPlanType::labels())
                            ->required()
                            ->default(SubscriptionPlanType::BASIC->value)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $plan = $state instanceof BackedEnum ? $state->value : $state;
                                $limits = [
                                    SubscriptionPlanType::BASIC->value => ['properties' => 100, 'tenants' => 50],
                                    SubscriptionPlanType::PROFESSIONAL->value => ['properties' => 500, 'tenants' => 250],
                                    SubscriptionPlanType::ENTERPRISE->value => ['properties' => 9999, 'tenants' => 9999],
                                ];

                                $planLimits = $limits[$plan] ?? $limits[SubscriptionPlanType::BASIC->value];

                                $set('max_properties', $planLimits['properties']);
                                $set('max_tenants', $planLimits['tenants']);
                            }),
                        
                        Forms\Components\Select::make('status')
                            ->label(__('subscriptions.labels.status'))
                            ->options(SubscriptionStatus::labels())
                            ->required()
                            ->default(SubscriptionStatus::ACTIVE->value),
                    ])->columns(3),

                Forms\Components\Section::make(__('subscriptions.sections.period'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label(__('subscriptions.labels.starts_at'))
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label(__('subscriptions.labels.expires_at'))
                            ->required()
                            ->after('starts_at')
                            ->default(now()->addYear()),
                    ])->columns(2),

                Forms\Components\Section::make(__('subscriptions.sections.limits'))
                    ->schema([
                        Forms\Components\TextInput::make('max_properties')
                            ->label(__('subscriptions.labels.max_properties'))
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->helperText(__('subscriptions.helper_text.max_properties')),
                        
                        Forms\Components\TextInput::make('max_tenants')
                            ->label(__('subscriptions.labels.max_tenants'))
                            ->numeric()
                            ->required()
                            ->default(50)
                            ->minValue(1)
                            ->helperText(__('subscriptions.helper_text.max_tenants')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Subscription $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('user.organization_name')
                    ->label(__('subscriptions.labels.organization'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label(__('subscriptions.labels.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('plan_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                    ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                        SubscriptionPlanType::BASIC->value => 'gray',
                        SubscriptionPlanType::PROFESSIONAL->value => 'info',
                        SubscriptionPlanType::ENTERPRISE->value => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionStatus::class))
                    ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                        SubscriptionStatus::ACTIVE->value => 'success',
                        SubscriptionStatus::EXPIRED->value => 'danger',
                        SubscriptionStatus::SUSPENDED->value => 'warning',
                        SubscriptionStatus::CANCELLED->value => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : ($record->daysUntilExpiry() <= 14 ? 'warning' : 'success')),
                
                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label(__('subscriptions.labels.days_left'))
                    ->state(fn (Subscription $record) => $record->daysUntilExpiry())
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 14 ? 'warning' : 'success'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderBy('expires_at', $direction)),
                
                Tables\Columns\TextColumn::make('max_properties')
                    ->label(__('subscriptions.labels.properties_limit'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('max_tenants')
                    ->label(__('subscriptions.labels.tenants_limit'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('subscriptions.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->label(__('subscriptions.filters.plan_type'))
                    ->options(SubscriptionPlanType::labels()),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('subscriptions.filters.status'))
                    ->options(SubscriptionStatus::labels()),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', SubscriptionStatus::ACTIVE->value)
                        ->where('expires_at', '>=', now())
                        ->where('expires_at', '<=', now()->addDays(14)))
                    ->label(__('subscriptions.filters.expiring_soon')),
                
                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now()))
                    ->label(__('subscriptions.filters.expired')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Actions\Action::make('renew')
                    ->label(__('subscriptions.actions.renew'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        Forms\Components\DateTimePicker::make('new_expires_at')
                            ->label(__('subscriptions.labels.new_expiration_date'))
                            ->required()
                            ->after('today')
                            ->default(now()->addYear()),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $record->update([
                            'expires_at' => $data['new_expires_at'],
                            'status' => SubscriptionStatus::ACTIVE->value,
                        ]);
                    })
                    ->visible(fn (Subscription $record) => in_array($record->status, [
                        SubscriptionStatus::ACTIVE,
                        SubscriptionStatus::EXPIRED,
                    ], true))
                    ->requiresConfirmation()
                    ->successNotificationTitle(__('subscriptions.notifications.renewed')),
                
                Actions\Action::make('suspend')
                    ->label(__('subscriptions.actions.suspend'))
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update(['status' => SubscriptionStatus::SUSPENDED->value]))
                    ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::ACTIVE)
                    ->successNotificationTitle(__('subscriptions.notifications.suspended')),
                
                Actions\Action::make('activate')
                    ->label(__('subscriptions.actions.activate'))
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update(['status' => SubscriptionStatus::ACTIVE->value]))
                    ->visible(fn (Subscription $record) => $record->status !== SubscriptionStatus::ACTIVE)
                    ->successNotificationTitle(__('subscriptions.notifications.activated')),
                
                Actions\Action::make('view_usage')
                    ->label(__('subscriptions.actions.view_usage'))
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('gray')
                    ->modalHeading(__('subscriptions.actions.subscription_usage'))
                    ->modalContent(fn (Subscription $record): \Illuminate\Contracts\View\View => view(
                        'filament.resources.subscription-usage',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('subscriptions.actions.close')),
                
                Actions\Action::make('send_renewal_reminder')
                    ->label(__('subscriptions.actions.send_reminder'))
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Subscription $record) {
                        // Send renewal reminder email
                        $record->user->notify(new \App\Notifications\SubscriptionExpiryWarningEmail($record));
                        
                        \Filament\Notifications\Notification::make()
                            ->title(__('subscriptions.notifications.reminder_sent'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::ACTIVE && $record->daysUntilExpiry() <= 30),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                Actions\BulkAction::make('bulk_renew')
                        ->label(__('subscriptions.actions.renew_selected'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('duration')
                                ->label(__('subscriptions.labels.renewal_duration'))
                                ->options([
                                    '1_month' => __('subscriptions.options.duration.1_month'),
                                    '3_months' => __('subscriptions.options.duration.3_months'),
                                    '6_months' => __('subscriptions.options.duration.6_months'),
                                    '1_year' => __('subscriptions.options.duration.1_year'),
                                ])
                                ->required()
                                ->default('1_year'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $renewed = 0;
                            $failed = 0;
                            
                            $durations = [
                                '1_month' => 1,
                                '3_months' => 3,
                                '6_months' => 6,
                                '1_year' => 12,
                            ];
                            
                            $months = $durations[$data['duration']];
                            
                            foreach ($records as $record) {
                                try {
                                    $newExpiry = $record->expires_at->isPast() 
                                        ? now()->addMonths($months)
                                        : $record->expires_at->addMonths($months);
                                    
                                    $record->update([
                                        'expires_at' => $newExpiry,
                                        'status' => SubscriptionStatus::ACTIVE->value,
                                    ]);
                                    $renewed++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(
                                    __('subscriptions.notifications.bulk_renewed', ['count' => $renewed])
                                    . ($failed > 0 ? __('subscriptions.notifications.bulk_failed_suffix', ['count' => $failed]) : '')
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                Actions\BulkAction::make('bulk_suspend')
                        ->label(__('subscriptions.actions.suspend_selected'))
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $suspended = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status === SubscriptionStatus::ACTIVE) {
                                    $record->update(['status' => SubscriptionStatus::SUSPENDED->value]);
                                    $suspended++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(
                                    __('subscriptions.notifications.bulk_suspended', ['count' => $suspended])
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                Actions\BulkAction::make('bulk_activate')
                        ->label(__('subscriptions.actions.activate_selected'))
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $activated = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status !== SubscriptionStatus::ACTIVE) {
                                    $record->update(['status' => SubscriptionStatus::ACTIVE->value]);
                                    $activated++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(
                                    __('subscriptions.notifications.bulk_activated', ['count' => $activated])
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Actions\ExportBulkAction::make()
                        ->label(__('subscriptions.actions.export_selected'))
                        ->icon('heroicon-o-arrow-down-tray'),
                ]),
            ])
            ->defaultSort('expires_at', 'asc');
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
