<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntegrationHealthResource\Pages;

use App\Filament\Resources\IntegrationHealthResource;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationHealthChecks extends ListRecords
{
    protected static string $resource = IntegrationHealthResource::class;
}