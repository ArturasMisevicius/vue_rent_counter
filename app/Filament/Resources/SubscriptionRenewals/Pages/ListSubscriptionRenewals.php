<?php

namespace App\Filament\Resources\SubscriptionRenewals\Pages;

use App\Filament\Resources\SubscriptionRenewals\SubscriptionRenewalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionRenewals extends ListRecords
{
    protected static string $resource = SubscriptionRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
