<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Pages;

use App\Filament\Resources\ListingLeads\ListingLeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateListingLead extends CreateRecord
{
    protected static string $resource = ListingLeadResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:leads,create';

    protected function getRedirectUrl(): string
    {
        return ListingLeadResource::getUrl('index');
    }
}
