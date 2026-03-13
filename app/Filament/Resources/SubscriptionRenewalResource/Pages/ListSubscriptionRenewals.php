<?php

namespace App\Filament\Resources\SubscriptionRenewalResource\Pages;

use App\Filament\Resources\SubscriptionRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionRenewals extends ListRecords
{
    protected static string $resource = SubscriptionRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}