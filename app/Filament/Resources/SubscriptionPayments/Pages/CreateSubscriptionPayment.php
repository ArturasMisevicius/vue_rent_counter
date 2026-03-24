<?php

namespace App\Filament\Resources\SubscriptionPayments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\SubscriptionPayments\SubscriptionPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionPayment extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = SubscriptionPaymentResource::class;
}
