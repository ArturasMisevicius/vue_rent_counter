<?php

namespace App\Filament\Resources\SubscriptionRenewalResource\Pages;

use App\Filament\Resources\SubscriptionRenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionRenewal extends EditRecord
{
    protected static string $resource = SubscriptionRenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}