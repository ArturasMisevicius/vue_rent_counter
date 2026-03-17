<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Superadmin\Users\CreateUserAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateUserAction::class)($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }
}
