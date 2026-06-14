<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Tariffs\TariffResource;
use Filament\Actions\EditAction;

class ViewTariff extends ViewRecord
{
    protected static string $resource = TariffResource::class;

    public function getTitle(): string
    {
        return __('admin.tariffs.view_title');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('admin.actions.edit')),
        ];
    }
}
