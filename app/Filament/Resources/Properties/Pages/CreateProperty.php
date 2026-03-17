<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreatePropertyAction::class)->handle(auth()->user()->organization, $data);
    }
}
