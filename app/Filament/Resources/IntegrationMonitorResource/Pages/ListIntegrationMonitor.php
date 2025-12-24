<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntegrationMonitorResource\Pages;

use App\Filament\Resources\IntegrationMonitorResource;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationMonitor extends ListRecords
{
    protected static string $resource = IntegrationMonitorResource::class;
}