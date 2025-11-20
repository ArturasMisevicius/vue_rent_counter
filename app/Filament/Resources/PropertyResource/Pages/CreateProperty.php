<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    /**
     * Mutate the form data before creating the record.
     * Automatically assigns tenant_id from authenticated user.
     *
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set tenant_id from authenticated user (Requirements 3.5)
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        return $data;
    }
}
