<?php

namespace App\Filament\Resources\SubscriptionRenewalResource\Pages;

use App\Filament\Resources\SubscriptionRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionRenewal extends ViewRecord
{
    protected static string $resource = SubscriptionRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}