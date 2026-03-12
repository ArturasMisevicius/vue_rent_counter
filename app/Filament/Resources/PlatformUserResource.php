<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\PlatformUserResource\Pages;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\GlobalSearch\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = null;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.platform_users.navigation_label');
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
                Forms\Components\Section::make(__('filament.resources.platform_users.sections.user_details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.resources.platform_users.fields.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('filament.resources.platform_users.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label(__('filament.resources.platform_users.fields.role'))
                            ->options([
                                UserRole::SUPERADMIN->value => __('filament.resources.platform_users.roles.superadmin'),
                                UserRole::ADMIN->value => __('filament.resources.platform_users.roles.admin'),
                                UserRole::MANAGER->value => __('filament.resources.platform_users.roles.manager'),
                                UserRole::TENANT->value => __('filament.resources.platform_users.roles.tenant'),
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('tenant_id')
                            ->label(__('filament.resources.platform_users.fields.organization'))
                            ->options(function () {
                                return Organization::query()
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->helperText(__('filament.resources.platform_users.helpers.organization')),

                        Forms\Components\TextInput::make('organization_name')
                            ->label(__('filament.resources.platform_users.fields.organization_name'))
                            ->maxLength(255)
                            ->helperText(__('filament.resources.platform_users.helpers.organization_name')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.resources.platform_users.sections.status'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.resources.platform_users.fields.is_active'))
                            ->default(true)
                            ->helperText(__('filament.resources.platform_users.helpers.is_active')),

                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label(__('filament.resources.platform_users.fields.last_login_at'))
                            ->disabled()
                            ->helperText(__('filament.resources.platform_users.helpers.last_login_at')),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label(__('filament.resources.platform_users.fields.email_verified_at'))
                            ->helperText(__('filament.resources.platform_users.helpers.email_verified_at')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.resources.platform_users.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament.resources.platform_users.fields.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('role')
                    ->label(__('filament.resources.platform_users.fields.role'))
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::SUPERADMIN => 'danger',
                        UserRole::ADMIN => 'warning',
                        UserRole::MANAGER => 'info',
                        UserRole::TENANT => 'success',
                    })
                    ->formatStateUsing(fn (UserRole $state): string => __('filament.resources.platform_users.roles.'.$state->value))
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label(__('filament.resources.platform_users.fields.organization'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.resources.platform_users.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('filament.resources.platform_users.fields.verified'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('filament.resources.platform_users.fields.last_login_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder(__('filament.resources.platform_users.placeholders.never')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.resources.platform_users.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('filament.resources.platform_users.fields.role'))
                    ->options([
                        'superadmin' => __('filament.resources.platform_users.roles.superadmin'),
                        'admin' => __('filament.resources.platform_users.roles.admin'),
                        'manager' => __('filament.resources.platform_users.roles.manager'),
                        'tenant' => __('filament.resources.platform_users.roles.tenant'),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label(__('filament.resources.platform_users.fields.organization'))
                    ->options(function () {
                        return Organization::query()
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.resources.platform_users.fields.status'))
                    ->placeholder(__('filament.resources.platform_users.filters.all_users'))
                    ->trueLabel(__('filament.resources.platform_users.filters.active_only'))
                    ->falseLabel(__('filament.resources.platform_users.filters.inactive_only')),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label(__('filament.resources.platform_users.fields.email_verified'))
                    ->placeholder(__('filament.resources.platform_users.filters.all_users'))
                    ->trueLabel(__('filament.resources.platform_users.filters.verified_only'))
                    ->falseLabel(__('filament.resources.platform_users.filters.unverified_only'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),

                Tables\Filters\Filter::make('last_login')
                    ->label(__('filament.resources.platform_users.fields.last_login_at'))
                    ->form([
                        Forms\Components\Select::make('period')
                            ->label(__('filament.resources.platform_users.fields.period'))
                            ->options([
                                '7' => __('filament.resources.platform_users.filters.period_7_days'),
                                '30' => __('filament.resources.platform_users.filters.period_30_days'),
                                '90' => __('filament.resources.platform_users.filters.period_90_days'),
                            ])
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['period'] ?? null,
                            fn (Builder $query, $period): Builder => $query->where(
                                'last_login_at',
                                '>=',
                                now()->subDays((int) $period)
                            ),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Actions\Action::make('reset_password')
                    ->label(__('filament.resources.platform_users.actions.reset_password.label'))
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.resources.platform_users.actions.reset_password.modal_heading'))
                    ->modalDescription(__('filament.resources.platform_users.actions.reset_password.modal_description'))
                    ->action(function (User $record) {
                        $temporaryPassword = Str::random(12);
                        $record->update([
                            'password' => bcrypt($temporaryPassword),
                        ]);

                        // TODO: Send email notification with temporary password
                        // This will be implemented when notification system is ready

                        Notification::make()
                            ->title(__('filament.resources.platform_users.actions.reset_password.notification_title'))
                            ->body(__('filament.resources.platform_users.actions.reset_password.notification_body', [
                                'password' => $temporaryPassword,
                            ]))
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('deactivate')
                    ->label(__('filament.resources.platform_users.actions.deactivate.label'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.resources.platform_users.actions.deactivate.modal_heading'))
                    ->modalDescription(__('filament.resources.platform_users.actions.deactivate.modal_description'))
                    ->visible(fn (User $record): bool => $record->is_active)
                    ->action(function (User $record) {
                        $record->update(['is_active' => false]);

                        Notification::make()
                            ->title(__('filament.resources.platform_users.actions.deactivate.notification_title'))
                            ->body(__('filament.resources.platform_users.actions.deactivate.notification_body', [
                                'name' => $record->name,
                            ]))
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('reactivate')
                    ->label(__('filament.resources.platform_users.actions.reactivate.label'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.resources.platform_users.actions.reactivate.modal_heading'))
                    ->modalDescription(__('filament.resources.platform_users.actions.reactivate.modal_description'))
                    ->visible(fn (User $record): bool => ! $record->is_active)
                    ->action(function (User $record) {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title(__('filament.resources.platform_users.actions.reactivate.notification_title'))
                            ->body(__('filament.resources.platform_users.actions.reactivate.notification_body', [
                                'name' => $record->name,
                            ]))
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('impersonate')
                    ->label(__('filament.resources.platform_users.actions.impersonate.label'))
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament.resources.platform_users.actions.impersonate.modal_heading'))
                    ->modalDescription(__('filament.resources.platform_users.actions.impersonate.modal_description'))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(__('filament.resources.platform_users.actions.impersonate.reason_label'))
                            ->required()
                            ->helperText(__('filament.resources.platform_users.actions.impersonate.reason_help')),
                    ])
                    ->action(function (User $record, array $data) {
                        // Log the impersonation start
                        OrganizationActivityLog::create([
                            'organization_id' => $record->tenant_id,
                            'user_id' => auth()->id(),
                            'action' => 'impersonate_start',
                            'resource_type' => 'User',
                            'resource_id' => $record->id,
                            'before_data' => null,
                            'after_data' => [
                                'target_user_id' => $record->id,
                                'target_user_name' => $record->name,
                                'reason' => $data['reason'],
                            ],
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);

                        // Store original user ID in session
                        session(['impersonating_from' => auth()->id()]);
                        session(['impersonation_reason' => $data['reason']]);
                        session(['impersonation_started_at' => now()]);

                        // Switch to target user
                        auth()->login($record);

                        Notification::make()
                            ->title(__('filament.resources.platform_users.actions.impersonate.notification_title'))
                            ->body(__('filament.resources.platform_users.actions.impersonate.notification_body', [
                                'name' => $record->name,
                            ]))
                            ->warning()
                            ->send();

                        return redirect()->route('filament.admin.pages.dashboard');
                    }),

                Actions\Action::make('view_activity')
                    ->label(__('filament.resources.platform_users.actions.view_activity'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->url(fn (User $record): string => route('filament.admin.resources.organization-activity-logs.index', [
                        'tableFilters' => [
                            'user_id' => ['value' => $record->id],
                        ],
                    ])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('bulk_deactivate')
                        ->label(__('filament.resources.platform_users.bulk_actions.deactivate.label'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('filament.resources.platform_users.bulk_actions.deactivate.modal_heading'))
                        ->modalDescription(__('filament.resources.platform_users.bulk_actions.deactivate.modal_description'))
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(fn (User $record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title(__('filament.resources.platform_users.bulk_actions.deactivate.notification_title'))
                                ->body(__('filament.resources.platform_users.bulk_actions.deactivate.notification_body', [
                                    'count' => $count,
                                ]))
                                ->success()
                                ->send();
                        }),

                    Actions\BulkAction::make('bulk_reactivate')
                        ->label(__('filament.resources.platform_users.bulk_actions.reactivate.label'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('filament.resources.platform_users.bulk_actions.reactivate.modal_heading'))
                        ->modalDescription(__('filament.resources.platform_users.bulk_actions.reactivate.modal_description'))
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(fn (User $record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title(__('filament.resources.platform_users.bulk_actions.reactivate.notification_title'))
                                ->body(__('filament.resources.platform_users.bulk_actions.reactivate.notification_body', [
                                    'count' => $count,
                                ]))
                                ->success()
                                ->send();
                        }),

                    Actions\BulkAction::make('bulk_send_notification')
                        ->label(__('filament.resources.platform_users.bulk_actions.send_notification.label'))
                        ->icon('heroicon-o-bell')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('subject')
                                ->label(__('filament.resources.platform_users.bulk_actions.send_notification.subject'))
                                ->required(),
                            Forms\Components\Textarea::make('message')
                                ->label(__('filament.resources.platform_users.bulk_actions.send_notification.message'))
                                ->required()
                                ->rows(5),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $count = $records->count();

                            // TODO: Implement actual notification sending
                            // This will be implemented when notification system is ready

                            Notification::make()
                                ->title(__('filament.resources.platform_users.bulk_actions.send_notification.notification_title'))
                                ->body(__('filament.resources.platform_users.bulk_actions.send_notification.notification_body', [
                                    'count' => $count,
                                ]))
                                ->success()
                                ->send();
                        }),

                    Actions\BulkAction::make('bulk_export')
                        ->label(__('filament.resources.platform_users.bulk_actions.export.label'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function (Collection $records) {
                            $csv = implode(',', [
                                __('filament.resources.platform_users.export.headers.name'),
                                __('filament.resources.platform_users.export.headers.email'),
                                __('filament.resources.platform_users.export.headers.role'),
                                __('filament.resources.platform_users.export.headers.organization'),
                                __('filament.resources.platform_users.export.headers.status'),
                                __('filament.resources.platform_users.export.headers.email_verified'),
                                __('filament.resources.platform_users.export.headers.last_login'),
                                __('filament.resources.platform_users.export.headers.created_at'),
                            ])."\n";

                            foreach ($records as $record) {
                                $csv .= implode(',', [
                                    '"'.str_replace('"', '""', $record->name).'"',
                                    '"'.str_replace('"', '""', $record->email).'"',
                                    '"'.str_replace('"', '""', __('filament.resources.platform_users.roles.'.$record->role->value)).'"',
                                    '"'.str_replace('"', '""', $record->organization_name ?? '').'"',
                                    $record->is_active ? __('filament.resources.platform_users.export.values.active') : __('filament.resources.platform_users.export.values.inactive'),
                                    $record->email_verified_at ? __('filament.resources.platform_users.export.values.yes') : __('filament.resources.platform_users.export.values.no'),
                                    $record->last_login_at?->format('Y-m-d H:i:s') ?? __('filament.resources.platform_users.placeholders.never'),
                                    $record->created_at->format('Y-m-d H:i:s'),
                                ])."\n";
                            }

                            $filename = 'platform-users-'.now()->format('Y-m-d-His').'.csv';

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, $filename, [
                                'Content-Type' => 'text/csv',
                            ]);
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPlatformUsers::route('/'),
            'create' => Pages\CreatePlatformUser::route('/create'),
            'edit' => Pages\EditPlatformUser::route('/{record}/edit'),
            'view' => Pages\ViewPlatformUser::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'organization_name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('filament.resources.platform_users.global_search.email') => $record->email,
            __('filament.resources.platform_users.global_search.role') => __('filament.resources.platform_users.roles.'.$record->role->value),
            __('filament.resources.platform_users.global_search.organization') => $record->organization_name ?? __('app.common.na'),
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->iconButton()
                ->icon('heroicon-m-pencil-square')
                ->url(static::getUrl('edit', ['record' => $record])),
        ];
    }
}
