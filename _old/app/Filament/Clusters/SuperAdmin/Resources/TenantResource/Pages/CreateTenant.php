<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\TenantResource\Pages;

use App\Contracts\TenantManagementInterface;
use App\Filament\Clusters\SuperAdmin\Resources\TenantResource;
use App\Support\Tenants\CreateTenantData;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $tenantService = app(TenantManagementInterface::class);

        $createData = new CreateTenantData(
            name: $data['name'],
            slug: $data['slug'],
            primaryContactEmail: $data['primary_contact_email'],
            plan: $data['plan'],
            domain: $data['domain'] ?? null,
            maxProperties: $data['max_properties'],
            maxUsers: $data['max_users'],
            trialEndsAt: $data['trial_ends_at'] ? Carbon::parse($data['trial_ends_at']) : null,
            subscriptionEndsAt: $data['subscription_ends_at'] ? Carbon::parse($data['subscription_ends_at']) : null,
            resourceQuotas: $data['resource_quotas'] ?? [],
            timezone: $data['timezone'],
            locale: $data['locale'],
            currency: $data['currency'],
            isActive: $data['is_active'] ?? true,
        );

        $tenant = $tenantService->createTenant($createData);

        Notification::make()
            ->title(__('superadmin.tenant.notifications.created'))
            ->body(__('superadmin.tenant.notifications.created_body', ['name' => $tenant->name]))
            ->success()
            ->send();

        return $tenant;
    }
}
