<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\AuditLogResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\AuditLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;

final class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getTitle(): string
    {
        return __('superadmin.audit.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_all')
                ->label(__('superadmin.audit.actions.export_all'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // TODO: Implement full export functionality
                    \Filament\Notifications\Notification::make()
                        ->title(__('superadmin.audit.notifications.export_started'))
                        ->body(__('superadmin.audit.notifications.export_all_started'))
                        ->info()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.audit.modals.export_all.heading'))
                ->modalDescription(__('superadmin.audit.modals.export_all.description'))
                ->modalSubmitActionLabel(__('superadmin.audit.actions.export')),

            Action::make('cleanup_old')
                ->label(__('superadmin.audit.actions.cleanup_old'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('before_date')
                        ->label(__('superadmin.audit.fields.cleanup_before_date'))
                        ->required()
                        ->default(now()->subMonths(6))
                        ->maxDate(now()->subMonth()),
                    
                    \Filament\Forms\Components\Checkbox::make('confirm_cleanup')
                        ->label(__('superadmin.audit.fields.confirm_cleanup'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    if (!$data['confirm_cleanup']) {
                        return;
                    }

                    $count = \App\Models\SuperAdminAuditLog::where('created_at', '<', $data['before_date'])
                        ->count();

                    // TODO: Implement cleanup with proper archiving
                    \Filament\Notifications\Notification::make()
                        ->title(__('superadmin.audit.notifications.cleanup_scheduled'))
                        ->body(__('superadmin.audit.notifications.cleanup_scheduled_body', [
                            'count' => $count,
                            'date' => \Carbon\Carbon::parse($data['before_date'])->format('M j, Y'),
                        ]))
                        ->warning()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.audit.modals.cleanup.heading'))
                ->modalDescription(__('superadmin.audit.modals.cleanup.description'))
                ->modalSubmitActionLabel(__('superadmin.audit.actions.cleanup')),

            Action::make('refresh')
                ->label(__('superadmin.audit.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->resetTable()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // TODO: Add audit log statistics widgets
        ];
    }
}