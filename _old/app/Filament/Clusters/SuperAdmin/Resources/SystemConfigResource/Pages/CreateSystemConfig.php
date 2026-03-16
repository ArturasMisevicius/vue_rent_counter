<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource\Pages;

use App\Filament\Clusters\SuperAdmin\Resources\SystemConfigResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSystemConfig extends CreateRecord
{
    protected static string $resource = SystemConfigResource::class;

    public function getTitle(): string
    {
        return __('superadmin.config.pages.create.title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
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

    protected function afterCreate(): void
    {
        // Log the configuration creation
        \App\Models\SuperAdminAuditLog::create([
            'admin_id' => auth()->id(),
            'action' => \App\Enums\AuditAction::SYSTEM_CONFIG_CREATED,
            'target_type' => \App\Models\SystemConfiguration::class,
            'target_id' => $this->getRecord()->id,
            'changes' => [
                'key' => $this->getRecord()->key,
                'category' => $this->getRecord()->category,
                'type' => $this->getRecord()->type,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        \Filament\Notifications\Notification::make()
            ->title(__('superadmin.config.notifications.created'))
            ->body(__('superadmin.config.notifications.created_body', [
                'key' => $this->getRecord()->key,
            ]))
            ->success()
            ->send();
    }
}