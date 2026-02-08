<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources;

use App\Contracts\TenantManagementInterface;
use App\Data\Tenant\CreateTenantData;
use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\Filament\Clusters\SuperAdmin;
use App\Filament\Clusters\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Organization;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class TenantResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $cluster = SuperAdmin::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('superadmin.navigation.tenants');
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.tenant.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.tenant.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.tenant.sections.basic_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('superadmin.tenant.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($set, ?string $state) => 
                                        $set('slug', \Illuminate\Support\Str::slug($state))
                                    ),

                                TextInput::make('slug')
                                    ->label(__('superadmin.tenant.fields.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),

                                TextInput::make('domain')
                                    ->label(__('superadmin.tenant.fields.domain'))
                                    ->maxLength(255)
                                    ->url()
                                    ->placeholder('https://example.com'),

                                TextInput::make('primary_contact_email')
                                    ->label(__('superadmin.tenant.fields.primary_contact_email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make(__('superadmin.tenants.sections.subscription'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('subscription_plan')
                                    ->label(__('superadmin.tenants.fields.subscription_plan'))
                                    ->options(SubscriptionPlan::class)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($set, ?string $state) {
                                        $limits = [
                                            'basic' => ['users' => 5, 'storage' => 1000, 'api_calls' => 1000],
                                            'professional' => ['users' => 25, 'storage' => 5000, 'api_calls' => 10000],
                                            'enterprise' => ['users' => 999, 'storage' => 50000, 'api_calls' => 100000],
                                            'custom' => ['users' => 10, 'storage' => 2000, 'api_calls' => 5000],
                                        ];
                                        
                                        if (isset($limits[$state])) {
                                            $set('max_users', $limits[$state]['users']);
                                            $set('max_storage_gb', $limits[$state]['storage']);
                                            $set('max_api_calls_per_month', $limits[$state]['api_calls']);
                                        }
                                    }),

                                TextInput::make('max_users')
                                    ->label(__('superadmin.tenants.fields.max_users'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(10),

                                TextInput::make('max_storage_gb')
                                    ->label(__('superadmin.tenants.fields.max_storage_gb'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->suffix('GB')
                                    ->default(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_api_calls_per_month')
                                    ->label(__('superadmin.tenants.fields.max_api_calls_per_month'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(100)
                                    ->default(1000),

                                Select::make('status')
                                    ->label(__('superadmin.tenants.fields.status'))
                                    ->options(TenantStatus::class)
                                    ->required()
                                    ->default(TenantStatus::TRIAL),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('trial_ends_at')
                                    ->label(__('superadmin.tenants.fields.trial_ends_at'))
                                    ->native(false)
                                    ->default(now()->addDays(14)),

                                DateTimePicker::make('subscription_ends_at')
                                    ->label(__('superadmin.tenants.fields.subscription_ends_at'))
                                    ->native(false),
                            ]),
                    ]),

                Section::make(__('superadmin.tenants.sections.billing'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('billing_email')
                                    ->label(__('superadmin.tenants.fields.billing_email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('billing_name')
                                    ->label(__('superadmin.tenants.fields.billing_name'))
                                    ->maxLength(255),
                            ]),

                        Textarea::make('billing_address')
                            ->label(__('superadmin.tenants.fields.billing_address'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('monthly_price')
                                    ->label(__('superadmin.tenants.fields.monthly_price'))
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->minValue(0),

                                TextInput::make('setup_fee')
                                    ->label(__('superadmin.tenants.fields.setup_fee'))
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->default(0),

                                Select::make('billing_cycle')
                                    ->label(__('superadmin.tenants.fields.billing_cycle'))
                                    ->options([
                                        'monthly' => __('superadmin.tenants.billing_cycles.monthly'),
                                        'quarterly' => __('superadmin.tenants.billing_cycles.quarterly'),
                                        'yearly' => __('superadmin.tenants.billing_cycles.yearly'),
                                    ])
                                    ->default('monthly'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('next_billing_date')
                                    ->label(__('superadmin.tenants.fields.next_billing_date'))
                                    ->native(false),

                                Toggle::make('auto_billing')
                                    ->label(__('superadmin.tenants.fields.auto_billing'))
                                    ->default(true),
                            ]),
                    ]),

                Section::make(__('superadmin.tenants.sections.quotas'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('current_users')
                                    ->label(__('superadmin.tenants.fields.current_users'))
                                    ->numeric()
                                    ->disabled()
                                    ->default(0)
                                    ->helperText(__('superadmin.tenants.help.current_users')),

                                TextInput::make('current_storage_gb')
                                    ->label(__('superadmin.tenants.fields.current_storage_gb'))
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('GB')
                                    ->default(0)
                                    ->helperText(__('superadmin.tenants.help.current_storage')),

                                TextInput::make('current_api_calls')
                                    ->label(__('superadmin.tenants.fields.current_api_calls'))
                                    ->numeric()
                                    ->disabled()
                                    ->default(0)
                                    ->helperText(__('superadmin.tenants.help.current_api_calls')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('enforce_quotas')
                                    ->label(__('superadmin.tenants.fields.enforce_quotas'))
                                    ->default(true)
                                    ->helperText(__('superadmin.tenants.help.enforce_quotas')),

                                Toggle::make('quota_notifications')
                                    ->label(__('superadmin.tenants.fields.quota_notifications'))
                                    ->default(true)
                                    ->helperText(__('superadmin.tenants.help.quota_notifications')),
                            ]),
                    ]),

                Section::make(__('superadmin.tenants.sections.settings'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('timezone')
                                    ->label(__('superadmin.tenants.fields.timezone'))
                                    ->options(collect(timezone_identifiers_list())->mapWithKeys(fn ($tz) => [$tz => $tz]))
                                    ->searchable()
                                    ->default('Europe/Vilnius'),

                                Select::make('locale')
                                    ->label(__('superadmin.tenants.fields.locale'))
                                    ->options([
                                        'en' => 'English',
                                        'lt' => 'Lietuvių',
                                    ])
                                    ->default('lt'),

                                Select::make('currency')
                                    ->label(__('superadmin.tenants.fields.currency'))
                                    ->options([
                                        'EUR' => 'Euro (€)',
                                        'USD' => 'US Dollar ($)',
                                        'GBP' => 'British Pound (£)',
                                    ])
                                    ->default('EUR'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('allow_registration')
                                    ->label(__('superadmin.tenants.fields.allow_registration'))
                                    ->default(true)
                                    ->helperText(__('superadmin.tenants.help.allow_registration')),

                                Toggle::make('require_email_verification')
                                    ->label(__('superadmin.tenants.fields.require_email_verification'))
                                    ->default(true)
                                    ->helperText(__('superadmin.tenants.help.require_email_verification')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('maintenance_mode')
                                    ->label(__('superadmin.tenants.fields.maintenance_mode'))
                                    ->default(false)
                                    ->helperText(__('superadmin.tenants.help.maintenance_mode')),

                                Toggle::make('api_access_enabled')
                                    ->label(__('superadmin.tenants.fields.api_access_enabled'))
                                    ->default(true)
                                    ->helperText(__('superadmin.tenants.help.api_access_enabled')),
                            ]),
                    ]),

                Section::make(__('superadmin.tenant.sections.suspension'))
                    ->schema([
                        DateTimePicker::make('suspended_at')
                            ->label(__('superadmin.tenant.fields.suspended_at'))
                            ->native(false)
                            ->disabled(),

                        Textarea::make('suspension_reason')
                            ->label(__('superadmin.tenant.fields.suspension_reason'))
                            ->rows(3)
                            ->disabled(),
                    ])
                    ->visible(fn (?Model $record) => $record?->suspended_at !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('superadmin.tenants.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('superadmin.tenants.fields.slug'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subscription_plan')
                    ->label(__('superadmin.tenants.fields.subscription_plan'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (SubscriptionPlan $state) => $state->getLabel())
                    ->color(fn (SubscriptionPlan $state) => $state->getColor()),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('superadmin.tenants.fields.status'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (TenantStatus $state) => $state->getLabel())
                    ->color(fn (TenantStatus $state) => $state->getColor()),

                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('superadmin.tenants.fields.current_users'))
                    ->counts('users')
                    ->sortable()
                    ->alignCenter()
                    ->description(fn (Organization $record) => 
                        "/ {$record->max_users} " . __('superadmin.tenants.fields.max_users')
                    ),

                Tables\Columns\TextColumn::make('current_storage_gb')
                    ->label(__('superadmin.tenants.fields.current_storage_gb'))
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 1) . ' GB')
                    ->sortable()
                    ->alignEnd()
                    ->description(fn (Organization $record) => 
                        "/ {$record->max_storage_gb} GB"
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->label(__('superadmin.tenants.fields.monthly_price'))
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('auto_billing')
                    ->label(__('superadmin.tenants.fields.auto_billing'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label(__('superadmin.tenants.fields.trial_ends_at'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label(__('superadmin.tenants.fields.next_billing_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('maintenance_mode')
                    ->label(__('superadmin.tenants.fields.maintenance_mode'))
                    ->boolean()
                    ->trueIcon('heroicon-o-wrench-screwdriver')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('superadmin.tenants.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_plan')
                    ->label(__('superadmin.tenants.fields.subscription_plan'))
                    ->options(SubscriptionPlan::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('superadmin.tenants.fields.status'))
                    ->options(TenantStatus::class),

                Tables\Filters\TernaryFilter::make('auto_billing')
                    ->label(__('superadmin.tenants.fields.auto_billing'))
                    ->boolean()
                    ->trueLabel(__('superadmin.common.status.active'))
                    ->falseLabel(__('superadmin.common.status.inactive'))
                    ->placeholder('All'),

                Tables\Filters\TernaryFilter::make('maintenance_mode')
                    ->label(__('superadmin.tenants.fields.maintenance_mode'))
                    ->boolean()
                    ->trueLabel('In Maintenance')
                    ->falseLabel('Active')
                    ->placeholder('All'),

                Tables\Filters\Filter::make('trial_ending_soon')
                    ->label('Trial Ending Soon')
                    ->query(fn (Builder $query) => 
                        $query->where('trial_ends_at', '<=', now()->addDays(7))
                              ->where('trial_ends_at', '>', now())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('over_quota')
                    ->label('Over Quota')
                    ->query(function (Builder $query) {
                        return $query->where(function ($q) {
                            $q->whereRaw('current_users > max_users')
                              ->orWhereRaw('current_storage_gb > max_storage_gb')
                              ->orWhereRaw('current_api_calls > max_api_calls_per_month');
                        });
                    })
                    ->toggle(),

                Tables\Filters\Filter::make('created_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('impersonate')
                    ->label(__('superadmin.tenants.actions.impersonate'))
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->url(fn (Organization $record) => route('filament.admin.pages.dashboard', ['tenant' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (Organization $record) => $record->status === TenantStatus::ACTIVE),

                Tables\Actions\Action::make('suspend')
                    ->label(__('superadmin.tenants.actions.suspend'))
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(__('superadmin.tenants.fields.suspension_reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Organization $record, array $data, TenantManagementInterface $tenantService) {
                        $tenantService->suspendTenant($record, $data['reason']);
                        
                        Notification::make()
                            ->title(__('superadmin.tenants.notifications.suspended'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('superadmin.tenants.modals.suspend.heading'))
                    ->modalDescription(__('superadmin.tenants.modals.suspend.description'))
                    ->visible(fn (Organization $record) => $record->status === TenantStatus::ACTIVE),

                Tables\Actions\Action::make('activate')
                    ->label(__('superadmin.tenants.actions.activate'))
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->action(function (Organization $record, TenantManagementInterface $tenantService) {
                        $tenantService->activateTenant($record);
                        
                        Notification::make()
                            ->title(__('superadmin.tenants.notifications.activated'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('superadmin.tenants.modals.activate.heading'))
                    ->modalDescription(__('superadmin.tenants.modals.activate.description'))
                    ->visible(fn (Organization $record) => $record->status !== TenantStatus::ACTIVE),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label(__('superadmin.tenants.actions.bulk_suspend'))
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label(__('superadmin.tenants.fields.suspension_reason'))
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, TenantManagementInterface $tenantService) {
                            $result = $tenantService->bulkUpdateTenants($records, ['suspended' => true, 'reason' => $data['reason']]);
                            
                            Notification::make()
                                ->title(__('superadmin.tenants.notifications.bulk_suspended', ['count' => $result->successful]))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading(__('superadmin.tenants.modals.bulk_suspend.heading'))
                        ->modalDescription(__('superadmin.tenants.modals.bulk_suspend.description'))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label(__('superadmin.tenants.actions.bulk_activate'))
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, TenantManagementInterface $tenantService) {
                            $result = $tenantService->bulkUpdateTenants($records, ['activated' => true]);
                            
                            Notification::make()
                                ->title(__('superadmin.tenants.notifications.bulk_activated', ['count' => $result->successful]))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
