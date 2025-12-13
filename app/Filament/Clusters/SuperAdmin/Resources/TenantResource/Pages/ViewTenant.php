<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\TenantResource\Pages;

use App\Contracts\TenantManagementInterface;
use App\Filament\Clusters\SuperAdmin\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

final class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('impersonate')
                ->label(__('superadmin.tenant.actions.impersonate'))
                ->icon('heroicon-o-user-circle')
                ->color('warning')
                ->url(fn () => route('filament.admin.pages.dashboard', ['tenant' => $this->record->slug]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->is_active),

            Actions\Action::make('suspend')
                ->label(__('superadmin.tenant.actions.suspend'))
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label(__('superadmin.tenant.fields.suspension_reason'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $tenantService = app(TenantManagementInterface::class);
                    $tenantService->suspendTenant($this->record, $data['reason']);
                    
                    Notification::make()
                        ->title(__('superadmin.tenant.notifications.suspended'))
                        ->success()
                        ->send();
                        
                    $this->refreshFormData(['suspended_at', 'suspension_reason', 'is_active']);
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->record->is_active),

            Actions\Action::make('activate')
                ->label(__('superadmin.tenant.actions.activate'))
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->action(function () {
                    $tenantService = app(TenantManagementInterface::class);
                    $tenantService->activateTenant($this->record);
                    
                    Notification::make()
                        ->title(__('superadmin.tenant.notifications.activated'))
                        ->success()
                        ->send();
                        
                    $this->refreshFormData(['suspended_at', 'suspension_reason', 'is_active']);
                })
                ->requiresConfirmation()
                ->visible(fn () => !$this->record->is_active),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make(__('superadmin.tenant.sections.overview'))
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label(__('superadmin.tenant.fields.name'))
                                    ->weight('bold'),
                                    
                                Components\TextEntry::make('slug')
                                    ->label(__('superadmin.tenant.fields.slug'))
                                    ->copyable(),
                                    
                                Components\TextEntry::make('plan')
                                    ->label(__('superadmin.tenant.fields.plan'))
                                    ->badge(),
                            ]),
                    ]),

                Components\Section::make(__('superadmin.tenant.sections.metrics'))
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('users_count')
                                    ->label(__('superadmin.tenant.fields.users_count'))
                                    ->state(fn () => $this->record->users()->count())
                                    ->badge()
                                    ->color('info'),
                                    
                                Components\TextEntry::make('properties_count')
                                    ->label(__('superadmin.tenant.fields.properties_count'))
                                    ->state(fn () => $this->record->properties()->count())
                                    ->badge()
                                    ->color('success'),
                                    
                                Components\TextEntry::make('storage_used')
                                    ->label(__('superadmin.tenant.fields.storage_used'))
                                    ->state(fn () => number_format($this->record->storage_used_mb, 1) . ' MB')
                                    ->badge()
                                    ->color('warning'),
                                    
                                Components\TextEntry::make('api_calls_today')
                                    ->label(__('superadmin.tenant.fields.api_calls_today'))
                                    ->badge()
                                    ->color('info'),
                            ]),
                    ]),

                Components\Section::make(__('superadmin.tenant.sections.subscription'))
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('trial_ends_at')
                                    ->label(__('superadmin.tenant.fields.trial_ends_at'))
                                    ->dateTime()
                                    ->placeholder(__('superadmin.tenant.placeholders.no_trial')),
                                    
                                Components\TextEntry::make('subscription_ends_at')
                                    ->label(__('superadmin.tenant.fields.subscription_ends_at'))
                                    ->dateTime()
                                    ->placeholder(__('superadmin.tenant.placeholders.no_subscription')),
                            ]),
                    ]),

                Components\Section::make(__('superadmin.tenant.sections.status'))
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\IconEntry::make('is_active')
                                    ->label(__('superadmin.tenant.fields.is_active'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                    
                                Components\TextEntry::make('last_activity_at')
                                    ->label(__('superadmin.tenant.fields.last_activity'))
                                    ->dateTime()
                                    ->since(),
                            ]),
                            
                        Components\TextEntry::make('suspended_at')
                            ->label(__('superadmin.tenant.fields.suspended_at'))
                            ->dateTime()
                            ->visible(fn () => $this->record->suspended_at !== null),
                            
                        Components\TextEntry::make('suspension_reason')
                            ->label(__('superadmin.tenant.fields.suspension_reason'))
                            ->visible(fn () => $this->record->suspension_reason !== null),
                    ]),

                Components\Section::make(__('superadmin.tenant.sections.timestamps'))
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label(__('superadmin.tenant.fields.created_at'))
                                    ->dateTime(),
                                    
                                Components\TextEntry::make('updated_at')
                                    ->label(__('superadmin.tenant.fields.updated_at'))
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}