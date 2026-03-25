<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Pages;

use App\Filament\Resources\UserKycProfiles\UserKycProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserKycProfiles extends ListRecords
{
    protected static string $resource = UserKycProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
