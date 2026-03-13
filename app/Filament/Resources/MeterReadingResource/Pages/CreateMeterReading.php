<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMeterReading extends CreateRecord
{
    protected static string $resource = MeterReadingResource::class;

    /**
     * Mutate form data before creating the record.
     * Automatically assigns tenant_id from authenticated user.
     * 
     * Requirements: 9.1, 10.1, 12.4
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tenant_id from authenticated user
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        // Set entered_by to current user
        $data['entered_by'] = auth()->id();
        
        return $data;
    }


}
