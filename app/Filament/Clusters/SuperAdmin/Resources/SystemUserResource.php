<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources;

use App\Contracts\SuperAdminUserInterface;
use App\Filament\Clusters\SuperAdmin;
use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SystemUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $cluster = SuperAdmin::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('superadmin.navigation.users');
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.user.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.user.sections.basic_info'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('superadmin.user.fields.name'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->label(__('superadmin.user.fields.email'))
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Select::make('tenant_id')
                                    ->label(__('superadmin.user.fields.organization'))
                                    ->relationship('organization', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('phone')
                                    ->label(__('superadmin.user.fields.phone'))
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make(__('superadmin.user.sections.status'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label(__('superadmin.user.fields.is_active'))
                                    ->default(true),

                                Toggle::make('email_verified_at')
                                    ->label(__('superadmin.user.fields.email_verified'))
                                    ->formatStateUsing(fn ($state) => $state !== null)
                                    ->dehydrateStateUsing(fn ($state) => $state ? now() : null),
                            ]),

                        DateTimePicker::make('suspended_at')
                            ->label(__('superadmin.user.fields.suspended_at'))
                            ->native(false)
                            ->disabled(),

                        Textarea::make('suspension_reason')
                            ->label(__('superadmin.user.fields.suspension_reason'))
                            ->rows(3)
                            ->disabled(),
                    ]),

                Section::make(__('superadmin.user.sections.activity'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('last_login_at')
                                    ->label(__('superadmin.user.fields.last_login_at'))
                                    ->native(false)
                                    ->disabled(),

                                TextInput::make('login_count')
                                    ->label(__('superadmin.user.fields.login_count'))
                                    ->numeric()
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('superadmin.user.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('superadmin.user.fields.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label(__('superadmin.user.fields.organization'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('superadmin.user.fields.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label(__('superadmin.user.fields.email_verified'))
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('superadmin.user.fields.last_login'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder(__('superadmin.user.placeholders.never_logged_in')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('superadmin.user.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label(__('superadmin.user.filters.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('superadmin.user.filters.is_active'))
                    ->boolean()
                    ->trueLabel(__('superadmin.user.filters.active'))
                    ->falseLabel(__('superadmin.user.filters.inactive'))
                    ->placeholder(__('superadmin.user.filters.all')),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label(__('superadmin.user.filters.email_verified'))
                    ->nullable()
                    ->trueLabel(__('superadmin.user.filters.verified'))
                    ->falseLabel(__('superadmin.user.filters.unverified'))
                    ->placeholder(__('superadmin.user.filters.all')),

                Tables\Filters\Filter::make('suspended')
                    ->label(__('superadmin.user.filters.suspended'))
                    ->query(fn (Builder $query) => $query->whereNotNull('suspended_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('recent_login')
                    ->label(__('superadmin.user.filters.recent_login'))
                    ->query(fn (Builder $query) => $query->where('last_login_at', '>', now()->subDays(30)))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('impersonate')
                    ->label(__('superadmin.user.actions.impersonate'))
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->action(function (User $record, SuperAdminUserInterface $userService) {
                        $session = $userService->impersonateUser($record);
                        
                        Notification::make()
                            ->title(__('superadmin.user.notifications.impersonation_started'))
                            ->body(__('superadmin.user.notifications.impersonation_started_body', ['name' => $record->name]))
                            ->warning()
                            ->persistent()
                            ->send();
                            
                        redirect()->route('filament.admin.pages.dashboard', ['tenant' => $record->organization->slug]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->is_active && !$record->hasRole('super_admin')),

                Tables\Actions\Action::make('suspend')
                    ->label(__('superadmin.user.actions.suspend'))
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label(__('superadmin.user.fields.suspension_reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data, SuperAdminUserInterface $userService) {
                        $userService->suspendUserGlobally($record, $data['reason']);
                        
                        Notification::make()
                            ->title(__('superadmin.user.notifications.suspended'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->is_active),

                Tables\Actions\Action::make('reactivate')
                    ->label(__('superadmin.user.actions.reactivate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (User $record, SuperAdminUserInterface $userService) {
                        $userService->reactivateUserGlobally($record);
                        
                        Notification::make()
                            ->title(__('superadmin.user.notifications.reactivated'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => !$record->is_active),

                Tables\Actions\Action::make('activity_report')
                    ->label(__('superadmin.user.actions.activity_report'))
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->url(fn (User $record) => static::getUrl('activity', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label(__('superadmin.user.bulk_actions.suspend'))
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label(__('superadmin.user.fields.suspension_reason'))
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, SuperAdminUserInterface $userService) {
                            $successful = 0;
                            foreach ($records as $record) {
                                try {
                                    $userService->suspendUserGlobally($record, $data['reason']);
                                    $successful++;
                                } catch (\Exception $e) {
                                    // Log error but continue
                                }
                            }
                            
                            Notification::make()
                                ->title(__('superadmin.user.notifications.bulk_suspended', ['count' => $successful]))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_reactivate')
                        ->label(__('superadmin.user.bulk_actions.reactivate'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, SuperAdminUserInterface $userService) {
                            $successful = 0;
                            foreach ($records as $record) {
                                try {
                                    $userService->reactivateUserGlobally($record);
                                    $successful++;
                                } catch (\Exception $e) {
                                    // Log error but continue
                                }
                            }
                            
                            Notification::make()
                                ->title(__('superadmin.user.notifications.bulk_reactivated', ['count' => $successful]))
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
            'index' => Pages\ListSystemUsers::route('/'),
            'create' => Pages\CreateSystemUser::route('/create'),
            'view' => Pages\ViewSystemUser::route('/{record}'),
            'edit' => Pages\EditSystemUser::route('/{record}/edit'),
            'activity' => Pages\UserActivityReport::route('/{record}/activity'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}