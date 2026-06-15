<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantKycProfiles\Pages;

use App\Filament\Resources\TenantKycProfiles\TenantKycProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListTenantKycProfiles extends ListRecords
{
    protected static string $resource = TenantKycProfileResource::class;
}
