<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Providers\ProviderResource;
use Filament\Actions\EditAction;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string
    {
        return __('admin.providers.view_title');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('admin.actions.edit')),
        ];
    }
}
