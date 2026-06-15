<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingGenerationLogs\Pages;

use App\Filament\Resources\BillingGenerationLogs\BillingGenerationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListBillingGenerationLogs extends ListRecords
{
    protected static string $resource = BillingGenerationLogResource::class;
}
