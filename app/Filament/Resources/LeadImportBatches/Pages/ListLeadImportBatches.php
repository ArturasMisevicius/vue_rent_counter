<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeadImportBatches\Pages;

use App\Filament\Resources\LeadImportBatches\LeadImportBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListLeadImportBatches extends ListRecords
{
    protected static string $resource = LeadImportBatchResource::class;
}
