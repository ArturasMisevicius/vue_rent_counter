<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\Superadmin\Users\UserDossierData;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return app(UserDossierData::class)->resolve($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('superadmin.users.actions.edit')),
        ];
    }
}
