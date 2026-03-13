<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceConfigurationResource\Pages;

use App\Filament\Resources\ServiceConfigurationResource;
use Filament\Resources\Pages\EditRecord;

class EditServiceConfiguration extends EditRecord
{
    protected static string $resource = ServiceConfigurationResource::class;
}

