<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-credit-card';
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
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'organization_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the organization for this subscription'),
                        
                        Forms\Components\Select::make('plan_type')
                            ->options(SubscriptionPlanType::labels())
                            ->required()
                            ->default(SubscriptionPlanType::BASIC->value)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $plan = $state instanceof BackedEnum ? $state->value : $state;
                                $limits = [
                                    SubscriptionPlanType::BASIC->value => ['properties' => 100, 'tenants' => 50],
                                    SubscriptionPlanType::PROFESSIONAL->value => ['properties' => 500, 'tenants' => 250],
                                    SubscriptionPlanType::ENTERPRISE->value => ['properties' => 9999, 'tenants' => 9999],
                                ];
                                $set('max_properties', $limits[$plan]['properties']);
                                $set('max_tenants', $limits[$plan]['tenants']);
                            }),
                        
                        Forms\Components\Select::make('status')
                            ->options(SubscriptionStatus::labels())
                            ->required()
                            ->default(SubscriptionStatus::ACTIVE->value),
                    ])->columns(3),

                Forms\Components\Section::make('Subscription Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->required()
                            ->after('starts_at')
                            ->default(now()->addYear()),
                    ])->columns(2),

                Forms\Components\Section::make('Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_properties')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->helperText('Maximum number of properties allowed'),
                        
                        Forms\Components\TextInput::make('max_tenants')
                            ->numeric()
                            ->required()
                            ->default(50)
                            ->minValue(1)
                            ->helperText('Maximum number of tenants allowed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
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
                    ->label('Days Left')
                    ->state(fn (Subscription $record) => $record->daysUntilExpiry())
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 14 ? 'warning' : 'success'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderBy('expires_at', $direction)),
                
                Tables\Columns\TextColumn::make('max_properties')
                    ->label('Properties Limit')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('max_tenants')
                    ->label('Tenants Limit')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options(SubscriptionPlanType::labels()),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options(SubscriptionStatus::labels()),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', SubscriptionStatus::ACTIVE->value)
                        ->where('expires_at', '>=', now())
                        ->where('expires_at', '<=', now()->addDays(14)))
                    ->label('Expiring Soon (14 days)'),
                
                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now()))
                    ->label('Expired'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        Forms\Components\DateTimePicker::make('new_expires_at')
                            ->label('New Expiration Date')
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
                        SubscriptionStatus::ACTIVE->value,
                        SubscriptionStatus::EXPIRED->value,
                    ], true))
                    ->requiresConfirmation()
                    ->successNotificationTitle('Subscription renewed successfully'),
                
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update(['status' => SubscriptionStatus::SUSPENDED->value]))
                    ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::ACTIVE->value)
                    ->successNotificationTitle('Subscription suspended successfully'),
                
                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update(['status' => SubscriptionStatus::ACTIVE->value]))
                    ->visible(fn (Subscription $record) => $record->status !== SubscriptionStatus::ACTIVE->value)
                    ->successNotificationTitle('Subscription activated successfully'),
                
                Tables\Actions\Action::make('view_usage')
                    ->label('Usage')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('gray')
                    ->modalHeading('Subscription Usage')
                    ->modalContent(fn (Subscription $record): \Illuminate\Contracts\View\View => view(
                        'filament.resources.subscription-usage',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                
                Tables\Actions\Action::make('send_renewal_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Subscription $record) {
                        // Send renewal reminder email
                        $record->user->notify(new \App\Notifications\SubscriptionExpiryWarningEmail($record));
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Renewal reminder sent')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Subscription $record) => $record->status === SubscriptionStatus::ACTIVE->value && $record->daysUntilExpiry() <= 30),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_renew')
                        ->label('Renew Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('duration')
                                ->label('Renewal Duration')
                                ->options([
                                    '1_month' => '1 Month',
                                    '3_months' => '3 Months',
                                    '6_months' => '6 Months',
                                    '1_year' => '1 Year',
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
                                ->title("Renewed {$renewed} subscriptions" . ($failed > 0 ? ", {$failed} failed" : ''))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $suspended = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status === SubscriptionStatus::ACTIVE->value) {
                                    $record->update(['status' => SubscriptionStatus::SUSPENDED->value]);
                                    $suspended++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Suspended {$suspended} subscriptions")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $activated = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status !== SubscriptionStatus::ACTIVE->value) {
                                    $record->update(['status' => SubscriptionStatus::ACTIVE->value]);
                                    $activated++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Activated {$activated} subscriptions")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected')
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
