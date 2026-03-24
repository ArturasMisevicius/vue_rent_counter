<?php

namespace App\Filament\Resources\SubscriptionPayments\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\SubscriptionPayments\SubscriptionPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPayment extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = SubscriptionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
