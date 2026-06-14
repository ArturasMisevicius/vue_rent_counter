<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Pages;

use App\Filament\Resources\ListingLeads\ListingLeadResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewListingLead extends ViewRecord
{
    protected static string $resource = ListingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
