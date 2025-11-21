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
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set tenant_id from session
        $data['tenant_id'] = session('tenant_id');
        
        // Set entered_by to current user
        $data['entered_by'] = auth()->id();
        
        return $data;
    }


}
