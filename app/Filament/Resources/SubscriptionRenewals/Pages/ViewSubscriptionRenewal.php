<?php

namespace App\Filament\Resources\SubscriptionRenewals\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\SubscriptionRenewals\SubscriptionRenewalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionRenewal extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = SubscriptionRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
