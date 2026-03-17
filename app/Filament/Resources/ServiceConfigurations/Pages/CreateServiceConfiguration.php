<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceConfiguration extends CreateRecord
{
    protected static string $resource = ServiceConfigurationResource::class;
}
