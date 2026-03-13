<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditSystemConfig extends EditRecord
{
    protected static string $resource = SystemConfigResource::class;

    public function getTitle(): string
    {
        return __('superadmin.config.pages.edit.title', [
            'key' => $this->getRecord()->key,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label(__('superadmin.config.actions.view'))
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->label(__('superadmin.config.actions.delete'))
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading(__('superadmin.config.modals.delete.heading'))
                ->modalDescription(__('superadmin.config.modals.delete.description'))
                ->modalSubmitActionLabel(__('superadmin.config.modals.delete.confirm'))
                ->successNotificationTitle(__('superadmin.config.notifications.deleted'))
                ->after(function () {
                    // Log the configuration deletion
                    \App\Models\SuperAdminAuditLog::create([
                        'admin_id' => auth()->id(),
                        'action' => \App\Enums\AuditAction::SYSTEM_CONFIG_DELETED,
                        'target_type' => \App\Models\SystemConfiguration::class,
                        'target_id' => $this->getRecord()->id,
                        'changes' => [
                            'key' => $this->getRecord()->key,
                            'category' => $this->getRecord()->category,
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        // Handle value encoding based on type
        if ($data['type'] === 'boolean') {
            $data['value'] = (bool) $data['value'];
        } elseif ($data['type'] === 'integer') {
            $data['value'] = (int) $data['value'];
        } elseif ($data['type'] === 'float') {
            $data['value'] = (float) $data['value'];
        } elseif (in_array($data['type'], ['array', 'json'])) {
            if (is_string($data['value'])) {
                $decoded = json_decode($data['value'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['value'] = $decoded;
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Get the changes that were made
        $changes = [];
        $original = $this->getRecord()->getOriginal();
        $current = $this->getRecord()->getAttributes();

        foreach (['key', 'category', 'type', 'value', 'description', 'is_sensitive'] as $field) {
            if (isset($original[$field]) && $original[$field] !== $current[$field]) {
                $changes[$field] = [
                    'from' => $original[$field],
                    'to' => $current[$field],
                ];
            }
        }

        // Log the configuration update
        \App\Models\SuperAdminAuditLog::create([
            'admin_id' => auth()->id(),
            'action' => \App\Enums\AuditAction::SYSTEM_CONFIG_UPDATED,
            'target_type' => \App\Models\SystemConfiguration::class,
            'target_id' => $this->getRecord()->id,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        \Filament\Notifications\Notification::make()
            ->title(__('superadmin.config.notifications.updated'))
            ->body(__('superadmin.config.notifications.updated_body', [
                'key' => $this->getRecord()->key,
            ]))
            ->success()
            ->send();
    }
}