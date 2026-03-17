<?php

namespace App\Filament\Resources\Tariffs\Pages;

use App\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Actions\Admin\Tariffs\UpdateTariffAction;
use App\Filament\Resources\Tariffs\TariffResource;
use App\Models\Tariff;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTariff extends EditRecord
{
    protected static string $resource = TariffResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateTariffAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (Tariff $record) => app(DeleteTariffAction::class)->handle($record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return TariffResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
