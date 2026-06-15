<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPeriods\Pages;

use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use App\Models\BillingPeriod;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingPeriod extends EditRecord
{
    protected static string $resource = BillingPeriodResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:billing,edit';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->authorize(fn (): bool => $this->record instanceof BillingPeriod
                    && BillingPeriodResource::canDelete($this->record)),
        ];
    }
}
