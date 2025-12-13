<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource\Pages;

use App\Contracts\SuperAdminUserInterface;
use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource;
use App\Models\User;
use App\ValueObjects\ActivityReport;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

final class ViewSystemUser extends ViewRecord
{
    protected static string $resource = SystemUserResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('superadmin.users.sections.basic_information'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->label(__('superadmin.users.fields.name'))
                                ->icon('heroicon-o-user'),
                            
                            TextEntry::make('email')
                                ->label(__('superadmin.users.fields.email'))
                                ->icon('heroicon-o-envelope')
                                ->copyable()
                                ->copyMessage(__('superadmin.users.messages.email_copied')),
                        ]),
                        
                        Grid::make(3)->schema([
                            IconEntry::make('is_active')
                                ->label(__('superadmin.users.fields.status'))
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger'),
                            
                            IconEntry::make('email_verified_at')
                                ->label(__('superadmin.users.fields.email_verified'))
                                ->boolean()
                                ->getStateUsing(fn ($record) => $record->email_verified_at !== null)
                                ->trueIcon('heroicon-o-shield-check')
                                ->falseIcon('heroicon-o-shield-exclamation')
                                ->trueColor('success')
                                ->falseColor('warning'),
                            
                            IconEntry::make('two_factor_enabled')
                                ->label(__('superadmin.users.fields.two_factor'))
                                ->boolean()
                                ->getStateUsing(fn ($record) => $record->two_factor_secret !== null)
                                ->trueIcon('heroicon-o-device-phone-mobile')
                                ->falseIcon('heroicon-o-device-phone-mobile')
                                ->trueColor('success')
                                ->falseColor('gray'),
                        ]),
                    ])
                    ->columns(1),

                Section::make(__('superadmin.users.sections.tenant_information'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('organization.name')
                                ->label(__('superadmin.users.fields.organization'))
                                ->icon('heroicon-o-building-office')
                                ->url(fn ($record) => $record->organization ? 
                                    route('filament.superadmin.resources.tenants.view', $record->organization) : null)
                                ->color('primary'),
                            
                            TextEntry::make('organization.status')
                                ->label(__('superadmin.users.fields.tenant_status'))
                                ->badge()
                                ->color(fn ($state) => $state?->getColor()),
                        ]),
                        
                        TextEntry::make('roles')
                            ->label(__('superadmin.users.fields.roles'))
                            ->badge()
                            ->getStateUsing(fn ($record) => $record->roles->pluck('name')->toArray())
                            ->color('info'),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record->organization !== null),

                Section::make(__('superadmin.users.sections.activity_summary'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('last_login_at')
                                ->label(__('superadmin.users.fields.last_login'))
                                ->dateTime()
                                ->since()
                                ->icon('heroicon-o-clock'),
                            
                            TextEntry::make('created_at')
                                ->label(__('superadmin.users.fields.created_at'))
                                ->dateTime()
                                ->since()
                                ->icon('heroicon-o-calendar'),
                            
                            TextEntry::make('updated_at')
                                ->label(__('superadmin.users.fields.updated_at'))
                                ->dateTime()
                                ->since()
                                ->icon('heroicon-o-pencil'),
                        ]),
                    ])
                    ->columns(1),

                Section::make(__('superadmin.users.sections.suspension_info'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('suspended_at')
                                ->label(__('superadmin.users.fields.suspended_at'))
                                ->dateTime()
                                ->icon('heroicon-o-no-symbol')
                                ->color('danger'),
                            
                            TextEntry::make('suspension_reason')
                                ->label(__('superadmin.users.fields.suspension_reason'))
                                ->icon('heroicon-o-exclamation-triangle')
                                ->color('danger'),
                        ]),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => $record->suspended_at !== null),

                Section::make(__('superadmin.users.sections.recent_activity'))
                    ->schema([
                        ViewEntry::make('activity_summary')
                            ->label('')
                            ->view('filament.superadmin.components.user-activity-summary')
                            ->viewData(fn ($record) => [
                                'activityReport' => $this->getUserActivityReport($record),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),

            Action::make('impersonate')
                ->label(__('superadmin.users.actions.impersonate'))
                ->icon('heroicon-o-user-circle')
                ->color('warning')
                ->visible(fn ($record) => $this->canImpersonateUser($record))
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.impersonate.heading'))
                ->modalDescription(__('superadmin.users.modals.impersonate.description'))
                ->modalSubmitActionLabel(__('superadmin.users.actions.start_impersonation'))
                ->action(function ($record) {
                    try {
                        $userService = app(SuperAdminUserInterface::class);
                        $session = $userService->impersonateUser($record);
                        
                        Notification::make()
                            ->title(__('superadmin.users.notifications.impersonation_started'))
                            ->body(__('superadmin.users.notifications.impersonation_started_body', [
                                'user' => $record->name,
                            ]))
                            ->success()
                            ->send();

                        // Redirect to the main application
                        return redirect()->to('/app');
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('superadmin.users.notifications.impersonation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('suspend')
                ->label(__('superadmin.users.actions.suspend'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn ($record) => $record->is_active && !$record->hasRole('super_admin'))
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label(__('superadmin.users.fields.suspension_reason'))
                        ->required()
                        ->maxLength(500),
                ])
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.suspend.heading'))
                ->modalDescription(__('superadmin.users.modals.suspend.description'))
                ->modalSubmitActionLabel(__('superadmin.users.actions.suspend'))
                ->action(function ($record, array $data) {
                    try {
                        $userService = app(SuperAdminUserInterface::class);
                        $userService->suspendUserGlobally($record, $data['reason']);
                        
                        Notification::make()
                            ->title(__('superadmin.users.notifications.user_suspended'))
                            ->body(__('superadmin.users.notifications.user_suspended_body', [
                                'user' => $record->name,
                            ]))
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'is_active',
                            'suspended_at',
                            'suspension_reason',
                        ]);
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('superadmin.users.notifications.suspension_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('reactivate')
                ->label(__('superadmin.users.actions.reactivate'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => !$record->is_active)
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.users.modals.reactivate.heading'))
                ->modalDescription(__('superadmin.users.modals.reactivate.description'))
                ->modalSubmitActionLabel(__('superadmin.users.actions.reactivate'))
                ->action(function ($record) {
                    try {
                        $userService = app(SuperAdminUserInterface::class);
                        $userService->reactivateUserGlobally($record);
                        
                        Notification::make()
                            ->title(__('superadmin.users.notifications.user_reactivated'))
                            ->body(__('superadmin.users.notifications.user_reactivated_body', [
                                'user' => $record->name,
                            ]))
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'is_active',
                            'suspended_at',
                            'suspension_reason',
                        ]);
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('superadmin.users.notifications.reactivation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('view_activity')
                ->label(__('superadmin.users.actions.view_activity'))
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn ($record) => static::$resource::getUrl('activity', ['record' => $record])),
        ];
    }

    private function canImpersonateUser(User $user): bool
    {
        // Cannot impersonate super admins or inactive users
        if ($user->hasRole('super_admin') || !$user->is_active) {
            return false;
        }

        // Check if current user has super admin role
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    private function getUserActivityReport(User $user): ActivityReport
    {
        $userService = app(SuperAdminUserInterface::class);
        return $userService->getUserActivityAcrossTenants($user);
    }
}