<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource\Pages;

use App\Contracts\SuperAdminUserInterface;
use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

final class EditSystemUser extends EditRecord
{
    protected static string $resource = SystemUserResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('superadmin.users.sections.basic_information'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('superadmin.users.fields.name'))
                                ->required()
                                ->maxLength(255)
                                ->autocomplete('name'),

                            TextInput::make('email')
                                ->label(__('superadmin.users.fields.email'))
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(User::class, 'email', ignoreRecord: true)
                                ->autocomplete('email'),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('password')
                                ->label(__('superadmin.users.fields.password'))
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                                ->dehydrated(fn ($state) => filled($state))
                                ->helperText(__('superadmin.users.help.password_leave_blank')),

                            TextInput::make('password_confirmation')
                                ->label(__('superadmin.users.fields.password_confirmation'))
                                ->password()
                                ->same('password')
                                ->dehydrated(false),
                        ]),
                    ])
                    ->columns(1),

                Section::make(__('superadmin.users.sections.status_settings'))
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label(__('superadmin.users.fields.is_active'))
                                ->helperText(__('superadmin.users.help.is_active'))
                                ->default(true),

                            Toggle::make('force_password_reset')
                                ->label(__('superadmin.users.fields.force_password_reset'))
                                ->helperText(__('superadmin.users.help.force_password_reset'))
                                ->default(false),
                        ]),

                        Grid::make(2)->schema([
                            DateTimePicker::make('email_verified_at')
                                ->label(__('superadmin.users.fields.email_verified_at'))
                                ->helperText(__('superadmin.users.help.email_verified_at')),

                            DateTimePicker::make('suspended_at')
                                ->label(__('superadmin.users.fields.suspended_at'))
                                ->helperText(__('superadmin.users.help.suspended_at'))
                                ->disabled(fn ($record) => $record?->hasRole('super_admin')),
                        ]),

                        Textarea::make('suspension_reason')
                            ->label(__('superadmin.users.fields.suspension_reason'))
                            ->maxLength(500)
                            ->rows(3)
                            ->visible(fn ($get) => filled($get('suspended_at')))
                            ->disabled(fn ($record) => $record?->hasRole('super_admin')),
                    ])
                    ->columns(1),

                Section::make(__('superadmin.users.sections.role_management'))
                    ->schema([
                        Select::make('roles')
                            ->label(__('superadmin.users.fields.roles'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('superadmin.users.help.roles'))
                            ->disabled(fn ($record) => $record?->hasRole('super_admin')),
                    ])
                    ->columns(1),

                Section::make(__('superadmin.users.sections.tenant_assignment'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.users.fields.organization'))
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('superadmin.users.help.organization'))
                            ->disabled(fn ($record) => $record?->hasRole('super_admin')),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),

            Action::make('reset_password')
                ->label(__('superadmin.users.actions.reset_password'))
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.reset_password.heading'))
                ->modalDescription(__('superadmin.users.modals.reset_password.description'))
                ->modalSubmitActionLabel(__('superadmin.users.actions.reset_password'))
                ->action(function ($record) {
                    // Generate a temporary password
                    $temporaryPassword = \Illuminate\Support\Str::random(12);
                    
                    $record->update([
                        'password' => Hash::make($temporaryPassword),
                        'force_password_reset' => true,
                    ]);

                    // TODO: Send email with temporary password
                    // This would typically be handled by a notification or job

                    Notification::make()
                        ->title(__('superadmin.users.notifications.password_reset'))
                        ->body(__('superadmin.users.notifications.password_reset_body', [
                            'password' => $temporaryPassword,
                        ]))
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Action::make('clear_sessions')
                ->label(__('superadmin.users.actions.clear_sessions'))
                ->icon('heroicon-o-device-phone-mobile')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.clear_sessions.heading'))
                ->modalDescription(__('superadmin.users.modals.clear_sessions.description'))
                ->modalSubmitActionLabel(__('superadmin.users.actions.clear_sessions'))
                ->action(function ($record) {
                    // Clear all user sessions
                    \Illuminate\Support\Facades\DB::table('sessions')
                        ->where('user_id', $record->id)
                        ->delete();

                    Notification::make()
                        ->title(__('superadmin.users.notifications.sessions_cleared'))
                        ->body(__('superadmin.users.notifications.sessions_cleared_body'))
                        ->success()
                        ->send();
                }),

            Action::make('disable_2fa')
                ->label(__('superadmin.users.actions.disable_2fa'))
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')
                ->visible(fn ($record) => $record->two_factor_secret !== null)
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.disable_2fa.heading'))
                ->modalDescription(__('superadmin.users.modals.disable_2fa.description'))
                ->modalSubmitActionLabel(__('superadmin.users.actions.disable_2fa'))
                ->action(function ($record) {
                    $record->update([
                        'two_factor_secret' => null,
                        'two_factor_recovery_codes' => null,
                        'two_factor_confirmed_at' => null,
                    ]);

                    Notification::make()
                        ->title(__('superadmin.users.notifications.2fa_disabled'))
                        ->body(__('superadmin.users.notifications.2fa_disabled_body'))
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->visible(fn ($record) => !$record->hasRole('super_admin'))
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.delete.heading'))
                ->modalDescription(__('superadmin.users.modals.delete.description'))
                ->before(function ($record) {
                    // Additional checks before deletion
                    if ($record->hasRole('super_admin')) {
                        Notification::make()
                            ->title(__('superadmin.users.notifications.cannot_delete_super_admin'))
                            ->danger()
                            ->send();
                        
                        $this->halt();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle suspension logic
        if (filled($data['suspended_at']) && !filled($data['suspension_reason'])) {
            $data['suspension_reason'] = __('superadmin.users.default_suspension_reason');
        }

        // If user is being unsuspended, clear suspension data
        if (!filled($data['suspended_at'])) {
            $data['suspension_reason'] = null;
        }

        // If user is being suspended, set is_active to false
        if (filled($data['suspended_at'])) {
            $data['is_active'] = false;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $userService = app(SuperAdminUserInterface::class);
        
        // Log the user update in audit trail
        \App\Models\SuperAdminAuditLog::create([
            'admin_id' => auth()->id(),
            'action' => \App\Enums\AuditAction::USER_UPDATED,
            'target_type' => User::class,
            'target_id' => $this->getRecord()->id,
            'tenant_id' => $this->getRecord()->organization_id,
            'changes' => $this->getRecord()->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Notification::make()
            ->title(__('superadmin.users.notifications.user_updated'))
            ->body(__('superadmin.users.notifications.user_updated_body', [
                'user' => $this->getRecord()->name,
            ]))
            ->success()
            ->send();
    }
}