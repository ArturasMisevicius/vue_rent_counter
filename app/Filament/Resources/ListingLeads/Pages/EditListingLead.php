<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Pages;

use App\Filament\Resources\ListingLeads\ListingLeadResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditListingLead extends EditRecord
{
    protected static string $resource = ListingLeadResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:leads,edit';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ListingLeadResource::getUrl('view', ['record' => $this->record]);
    }
}
