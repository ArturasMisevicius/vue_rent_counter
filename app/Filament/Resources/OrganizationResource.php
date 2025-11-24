<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'System Management';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
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
                Forms\Components\Section::make('Organization Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from name, but can be customized'),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('domain')
                            ->maxLength(255)
                            ->helperText('Custom domain for this organization (optional)'),
                    ])->columns(2),

                Forms\Components\Section::make('Subscription & Limits')
                    ->schema([
                        Forms\Components\Select::make('plan')
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
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('max_users')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1),
                        
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial End Date')
                            ->helperText('Leave empty if not on trial'),
                        
                        Forms\Components\DateTimePicker::make('subscription_ends_at')
                            ->label('Subscription End Date')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Regional Settings')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->options([
                                'Europe/Vilnius' => 'Europe/Vilnius',
                                'Europe/London' => 'Europe/London',
                                'America/New_York' => 'America/New_York',
                                'UTC' => 'UTC',
                            ])
                            ->required()
                            ->default('Europe/Vilnius'),
                        
                        Forms\Components\Select::make('locale')
                            ->options([
                                'lt' => 'Lithuanian',
                                'en' => 'English',
                                'ru' => 'Russian',
                            ])
                            ->required()
                            ->default('lt'),
                        
                        Forms\Components\Select::make('currency')
                            ->options([
                                'EUR' => 'EUR (€)',
                                'USD' => 'USD ($)',
                            ])
                            ->required()
                            ->default('EUR'),
                    ])->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive organizations cannot access the system'),
                        
                        Forms\Components\DateTimePicker::make('suspended_at')
                            ->label('Suspended At')
                            ->disabled()
                            ->helperText('Set automatically when suspended'),
                        
                        Forms\Components\Textarea::make('suspension_reason')
                            ->maxLength(500)
                            ->rows(3)
                            ->disabled()
                            ->helperText('Reason for suspension'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('plan')
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
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('properties_count')
                    ->counts('properties')
                    ->label('Properties')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subscription_ends_at')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->subscription_ends_at?->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options(SubscriptionPlanType::labels()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All organizations')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\Filter::make('subscription_expired')
                    ->query(fn (Builder $query): Builder => $query->where('subscription_ends_at', '<', now()))
                    ->label('Expired Subscriptions'),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('subscription_ends_at', '>=', now())
                        ->where('subscription_ends_at', '<=', now()->addDays(14)))
                    ->label('Expiring Soon (14 days)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Suspension Reason')
                            ->maxLength(500),
                    ])
                    ->action(function (Organization $record, array $data) {
                        $record->suspend($data['reason']);
                    })
                    ->visible(fn (Organization $record) => !$record->isSuspended())
                    ->successNotificationTitle('Organization suspended successfully'),
                
                Tables\Actions\Action::make('reactivate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Organization $record) => $record->reactivate())
                    ->visible(fn (Organization $record) => $record->isSuspended())
                    ->successNotificationTitle('Organization reactivated successfully'),
                
                Tables\Actions\Action::make('impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate Organization Admin')
                    ->modalDescription('You will be logged in as this organization\'s admin. All actions will be logged.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Reason for Impersonation')
                            ->helperText('This will be logged in the audit trail')
                            ->maxLength(500),
                    ])
                    ->action(function (Organization $record, array $data) {
                        // Get the organization's admin user
                        $adminUser = $record->users()->where('role', 'admin')->first();
                        
                        if (!$adminUser) {
                            \Filament\Notifications\Notification::make()
                                ->title('No admin user found')
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
                            ->title('Impersonation started')
                            ->success()
                            ->send();
                        
                        // Redirect to their dashboard
                        return redirect()->route('filament.admin.pages.dashboard');
                    })
                    ->visible(fn (Organization $record) => $record->is_active),
                
                Tables\Actions\Action::make('view_analytics')
                    ->label('Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->url(fn (Organization $record): string => '#')
                    ->openUrlInNewTab(false)
                    ->tooltip('View detailed analytics for this organization'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->required()
                                ->label('Suspension Reason')
                                ->helperText('This reason will be applied to all selected organizations')
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
                                ->title("Suspended {$suspended} organizations" . ($failed > 0 ? ", {$failed} failed" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_reactivate')
                        ->label('Reactivate Selected')
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
                                ->title("Reactivated {$reactivated} organizations" . ($failed > 0 ? ", {$failed} failed" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_change_plan')
                        ->label('Change Plan')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('new_plan')
                                ->label('New Plan')
                                ->options(SubscriptionPlanType::labels())
                                ->required()
                                ->live()
                                ->helperText('Resource limits will be updated automatically'),
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
                                ->title("Updated {$updated} organizations" . ($failed > 0 ? ", {$failed} failed" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected')
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
}
