<?php

namespace App\Filament\Resources\SubscriptionPayments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\SubscriptionPayments\SubscriptionPaymentResource;
use Filament\Actions\EditAction;

class ViewSubscriptionPayment extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = SubscriptionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
