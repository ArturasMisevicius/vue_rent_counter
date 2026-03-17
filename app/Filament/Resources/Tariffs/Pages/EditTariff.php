<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Models\Tariff;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTariff extends EditRecord
{
    protected static string $resource = TariffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (Tariff $record) => app(DeleteTariffAction::class)->handle($record)),
        ];
    }
}
