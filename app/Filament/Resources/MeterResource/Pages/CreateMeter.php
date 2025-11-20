<?php

namespace App\Filament\Resources\MeterResource\Pages;

use App\Filament\Resources\MeterResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMeter extends CreateRecord
{
    protected static string $resource = MeterResource::class;

    /**
     * Mutate the form data before creating the record.
     * Automatically assigns tenant_id from authenticated user.
     *
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set tenant_id from authenticated user
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        return $data;
    }
}
