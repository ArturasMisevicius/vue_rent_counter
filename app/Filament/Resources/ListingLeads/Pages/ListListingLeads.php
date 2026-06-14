<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListingLeads\Pages;

use App\Filament\Pages\LeadImport;
use App\Filament\Pages\LeadReports;
use App\Filament\Resources\ListingLeads\ListingLeadResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListListingLeads extends ListRecords
{
    protected static string $resource = ListingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label(__('admin.leads.actions.import_csv'))
                ->url(LeadImport::getUrl()),
            Action::make('reports')
                ->label(__('admin.leads.actions.reports'))
                ->color('gray')
                ->url(LeadReports::getUrl()),
            CreateAction::make(),
        ];
    }
}
