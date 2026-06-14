<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadOutreachTemplates\Pages;

use App\Filament\Resources\LeadOutreachTemplates\LeadOutreachTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeadOutreachTemplates extends ListRecords
{
    protected static string $resource = LeadOutreachTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
