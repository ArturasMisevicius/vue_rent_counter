<?php

namespace App\Filament\Resources\SubscriptionRenewals\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\SubscriptionRenewals\SubscriptionRenewalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionRenewal extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = SubscriptionRenewalResource::class;
}
