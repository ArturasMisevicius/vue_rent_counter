<?php

namespace App\Filament\Resources\SubscriptionRenewals\Pages;

use App\Filament\Resources\SubscriptionRenewals\SubscriptionRenewalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionRenewal extends EditRecord
{
    protected static string $resource = SubscriptionRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
