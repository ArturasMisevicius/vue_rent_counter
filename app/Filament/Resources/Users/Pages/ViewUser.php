<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\Superadmin\Users\UserDossierData;
use Illuminate\Database\Eloquent\Model;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return app(UserDossierData::class)->resolve($key);
    }
}
