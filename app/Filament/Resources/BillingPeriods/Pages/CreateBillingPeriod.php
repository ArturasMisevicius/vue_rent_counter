<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPeriods\Pages;

use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use App\Filament\Support\Admin\OrganizationContext;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingPeriod extends CreateRecord
{
    protected static string $resource = BillingPeriodResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:billing,create';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] ??= app(OrganizationContext::class)->currentOrganizationId();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return BillingPeriodResource::getUrl('index');
    }
}
