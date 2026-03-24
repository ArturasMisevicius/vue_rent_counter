<?php

namespace App\Filament\Resources\SubscriptionPayments\Pages;

use App\Filament\Resources\SubscriptionPayments\SubscriptionPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionPayment extends ViewRecord
{
    protected static string $resource = SubscriptionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
