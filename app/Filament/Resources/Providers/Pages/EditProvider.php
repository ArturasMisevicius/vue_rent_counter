<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Actions\Admin\Providers\DeleteProviderAction;
use App\Filament\Actions\Admin\Providers\UpdateProviderAction;
use App\Filament\Resources\Providers\ProviderResource;
use App\Models\Provider;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateProviderAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (Provider $record) => app(DeleteProviderAction::class)->handle($record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ProviderResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
